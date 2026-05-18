<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Soutenance;
use App\Models\Professor;
use Carbon\Carbon;

class PlanningController extends Controller
{
    public function saveDates(Request $request)
    {
        $request->validate([
            'date_soutenance' => 'required|date',
        ]);

        session([
            'date_soutenance' => $request->date_soutenance,
        ]);

        return redirect()->back()->with('success', 'Date enregistrée avec succès !');
    }

    //gardée mais pas utilisée dans les routes pour l'instant
    public function generateAffectation()
    {
        Student::query()->update(['encadrant_id' => null]);
        $studentsByFiliere = Student::all()->groupBy('filiere');
        $profs = Professor::all()->values();

        if ($profs->isEmpty()) {
            return response()->json(['error' => 'Aucun professeur trouvé !']);
        }

        foreach ($studentsByFiliere as $filiere => $students) {
            $i = 0;
            foreach ($students as $student) {
                $student->encadrant_id = $profs[$i % count($profs)]->id;
                $student->save();
                $i++;
            }
        }

        return response()->json(['message' => 'Affectation générée avec succès !']);
    }

    public function generatePlanning()
    {
        //vider les anciennes soutenances
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Soutenance::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $students = Student::all();

        if ($students->isEmpty()) {
            return response()->json(['message' => 'Aucun étudiant trouvé.']);
        }

        //créneaux avec pause 1h entre soutenances
        $timeSlots = [
            ["debut" => "09:00", "fin" => "10:00"],
            ["debut" => "11:00", "fin" => "12:00"],
            ["debut" => "14:00", "fin" => "15:00"],
            ["debut" => "16:00", "fin" => "17:30"],
        ];

        $salles = [
            "Amphi A", "Salle 5 AB", "Salle 4 AB", "Salle 17 AB",
            "Salle 16 AB", "Salle 22 AB", "Salle 23 AB",
            "Salle 24 AB", "Salle 21 AB", "Salle 15 AB"
        ];

        //l'utilisateur entre seulement la date de début
        $date_soutenance = Carbon::parse(request('date'));

        $dates = [
            $date_soutenance->copy()->toDateString(),
            $date_soutenance->copy()->addDay()->toDateString(),
            $date_soutenance->copy()->addDays(2)->toDateString(),
        ];

        //générer tous les créneaux possibles
        $creneaux = [];
        foreach ($dates as $date) {
            foreach ($timeSlots as $slot) {
                foreach ($salles as $salle) {
                    $creneaux[] = [
                        "date"  => $date,
                        "debut" => $slot["debut"],
                        "fin"   => $slot["fin"],
                        "salle" => $salle,
                    ];
                }
            }
        }

        $affectes = 0;
        $erreurs  = [];

        foreach ($students as $index => $student) {

            if (!isset($creneaux[$index])) {
                $erreurs[] = "Pas assez de créneaux pour {$student->nom}";
                continue;
            }

            $creneau = $creneaux[$index];

            //profs occupés dans ce créneau
            $soutenancesExistantes = Soutenance::where('date_soutenance', $creneau['date'])
                ->where('heure_debut', $creneau['debut'])
                ->get();

            $profsOccupes = $soutenancesExistantes->flatMap(function ($s) {
                return [$s->encadrant_id, $s->jury_id1, $s->jury_id2];
            })->filter()->unique()->toArray();

            //Chercher 2 profs Informatique disponibles
            $jury = Professor::where('discipline', 'Informatique')
                ->where('id', '!=', $student->encadrant_id)
                ->whereNotIn('id', $profsOccupes)
                ->inRandomOrder()
                ->limit(2)
                ->get();

            //Si pas assez : prendre n'importe quel prof disponible
            if ($jury->count() < 2) {
                $jury = Professor::where('id', '!=', $student->encadrant_id)
                    ->whereNotIn('id', $profsOccupes)
                    ->inRandomOrder()
                    ->limit(2)
                    ->get();
            }

            if ($jury->count() < 2) {
                $erreurs[] = "Pas assez de profs pour {$student->nom}";
                continue;
            }

            Soutenance::create([
                "student_id"      => $student->id,
                "date_soutenance" => $creneau["date"],
                "heure_debut"     => $creneau["debut"],
                "heure_fin"       => $creneau["fin"],
                "salle"           => $creneau["salle"],
                "encadrant_id"    => $student->encadrant_id,
                "jury_id1"        => $jury[0]->id,
                "jury_id2"        => $jury[1]->id,
            ]);

            $affectes++;
        }

        return response()->json([
            'message'    => 'Planning généré avec succès !',
            'affectes'  => $affectes,
            'total'   => $students->count(),
            'date_debut'  => $date_soutenance->toDateString(),
            'date_fin' => $date_soutenance->addDays(2)->toDateString(),
            'erreurs' => $erreurs,
        ]);
    }
}