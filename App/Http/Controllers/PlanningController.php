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
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date|after_or_equal:date_debut',
        ]);

        $debut = Carbon::parse($request->date_debut);
        $fin   = Carbon::parse($request->date_fin);

        $jours = [];
        $current = $debut->copy();
        while ($current->lte($fin)) {
            $jours[] = $current->toDateString();
            $current->addDay();
        }

        session([
            'date_debut'        => $request->date_debut,
            'date_fin'          => $request->date_fin,
            'jours_soutenance'  => $jours,
        ]);

        return redirect()->back()->with('success',
            'Dates enregistrées : ' . count($jours) . ' jour(s) du ' .
            $debut->format('d/m/Y') . ' au ' . $fin->format('d/m/Y')
        );
    }

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
        // Vider les anciennes soutenances
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Soutenance::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ETAPE 1 : Mélanger et regrouper les binômes
        $binomesTraites = [];
        $groupes = collect();

        // Mélanger équitablement par filière
        $gi   = Student::where('filiere', 'GI')->get()->shuffle();
        $data = Student::where('filiere', 'DATA')->get()->shuffle();
        $tdai = Student::where('filiere', 'TDAI')->get()->shuffle();

        $max = max($gi->count(), $data->count(), $tdai->count());
        $studentsMelanges = collect();

        for ($i = 0; $i < $max; $i++) {
            if ($gi->has($i))   $studentsMelanges->push($gi[$i]);
            if ($data->has($i)) $studentsMelanges->push($data[$i]);
            if ($tdai->has($i)) $studentsMelanges->push($tdai[$i]);
        }

        foreach ($studentsMelanges as $student) {

                if (in_array($student->id, $binomesTraites)) continue;

                // Vérifier binôme avec == 1
                if ($student->binome == 1 && !empty($student->sujet)) {

                    $partner = Student::where('sujet', $student->sujet)
                    ->where('id', '!=', $student->id)
                    ->where('binome', 1)
                    ->whereNotIn('id', $binomesTraites)
                    ->first();

                    if ($partner) {
                        $groupes->push([
                            'etudiants'    => collect([$student, $partner]),
                            'langue'       => $student->langue ?? 'FR',
                            'encadrant_id' => $student->encadrant_id,
                        ]);
                        $binomesTraites[] = $student->id;
                        $binomesTraites[] = $partner->id;

                        // LOG pour vérifier
                        \Log::info('Binôme détecté: ' . $student->nom . ' + ' . $partner->nom);
                        continue;
                    }
                }

                // Étudiant seul
                $groupes->push([
                    'etudiants'    => collect([$student]),
                    'langue'       => $student->langue ?? 'FR',
                    'encadrant_id' => $student->encadrant_id,
                ]);
                $binomesTraites[] = $student->id;
        }

        

        // ETAPE 2 : Créneaux
        $timeSlots = [
            ["debut" => "09:00", "fin" => "10:00"],
            ["debut" => "11:00", "fin" => "12:00"],
            ["debut" => "14:00", "fin" => "15:00"],
            ["debut" => "16:00", "fin" => "17:00"],
        ];

        $salles = session('salles', [
            'Amphi A', 'Salle 4 AB', 'Salle 5 AB',
            'Salle 15 AB', 'Salle 16 AB', 'Salle 17 AB',
        ]);

        $jours = session('jours_soutenance', []);

        if (empty($jours)) {
            $dateDebut = Carbon::parse(request('date', now()->toDateString()));
            $jours = [
                $dateDebut->copy()->toDateString(),
                $dateDebut->copy()->addDay()->toDateString(),
                $dateDebut->copy()->addDays(2)->toDateString(),
            ];
        }

        $creneaux = [];
        foreach ($jours as $date) {
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

        // ETAPE 3 : Assigner un créneau à chaque groupe
        $affectes = 0;
        $erreurs  = [];

        foreach ($groupes as $index => $groupe) {

            if (!isset($creneaux[$index])) {
                $noms = $groupe['etudiants']->map(fn($e) => $e->nom)->implode(', ');
                $erreurs[] = "Pas assez de créneaux pour : " . $noms;
                continue;
            }

            $creneau      = $creneaux[$index];
            $encadrant_id = $groupe['encadrant_id'];
            $langue       = strtoupper($groupe['langue'] ?? 'FR');

            // Profs occupés dans ce créneau
            $soutenancesExistantes = Soutenance::where('date_soutenance', $creneau['date'])
                ->where('heure_debut', $creneau['debut'])
                ->get();

            $profsOccupes = $soutenancesExistantes->flatMap(function ($s) {
                return [$s->encadrant_id, $s->jury_id1, $s->jury_id2];
            })->filter()->unique()->toArray();

            if ($encadrant_id) {
                $profsOccupes[] = $encadrant_id;
            }

            // ETAPE 4 : Jury selon la langue
            if ($langue === 'EN') {

                \Log::info('Soutenance EN pour: ' . $groupe['etudiants']->first()->nom);
                \Log::info('ProfsOccupes: ' . implode(',', $profsOccupes));

                $profAnglais = Professor::where('discipline', 'Anglais')
                    ->whereNotIn('id', $profsOccupes)
                    ->inRandomOrder()
                    ->first();

                \Log::info('Prof Anglais trouvé: ' . ($profAnglais ? $profAnglais->nom : 'AUCUN'));

                $profInfo = Professor::where('discipline', 'Informatique')
                    ->whereNotIn('id', $profsOccupes)
                    ->when($profAnglais, fn($q) => $q->where('id', '!=', $profAnglais->id))
                    ->inRandomOrder()
                    ->first();

                \Log::info('Prof Info trouvé: ' . ($profInfo ? $profInfo->nom : 'AUCUN'));

                if (!$profAnglais || !$profInfo) {
                    \Log::warning('FALLBACK utilisé pour: ' . $groupe['etudiants']->first()->nom);
                    $jury = Professor::whereNotIn('id', $profsOccupes)
                        ->inRandomOrder()
                        ->limit(2)
                        ->get();
                    if ($jury->count() < 2) {
                        $erreurs[] = "Pas assez de profs pour soutenance EN : " .
                            $groupe['etudiants']->first()->nom;
                        continue;
                    }
                    $jury_id1 = $jury[0]->id;
                    $jury_id2 = $jury[1]->id;
                    } else {
                    $jury_id1 = $profAnglais->id;
                    $jury_id2 = $profInfo->id;
                }
            } else {

                $jury = Professor::where('discipline', 'Informatique')
                    ->whereNotIn('id', $profsOccupes)
                    ->inRandomOrder()
                    ->limit(2)
                    ->get();

                if ($jury->count() < 2) {
                    $jury = Professor::whereNotIn('id', $profsOccupes)
                        ->inRandomOrder()
                        ->limit(2)
                        ->get();
                }

                if ($jury->count() < 2) {
                    $erreurs[] = "Pas assez de profs pour : " .
                                 $groupe['etudiants']->first()->nom;
                    continue;
                }

                $jury_id1 = $jury[0]->id;
                $jury_id2 = $jury[1]->id;
            }

            // ETAPE 5 / Créer la soutenance
            $etudiant1 = $groupe['etudiants']->first();
            $etudiant2 = $groupe['etudiants']->count() > 1
                         ? $groupe['etudiants']->last()
                         : null;

            Soutenance::create([
                "student_id"        => $etudiant1->id,
                "binome_student_id" => $etudiant2?->id,
                "date_soutenance"   => $creneau["date"],
                "heure_debut"       => $creneau["debut"],
                "heure_fin"         => $creneau["fin"],
                "salle"             => $creneau["salle"],
                "encadrant_id"      => $encadrant_id,
                "jury_id1"          => $jury_id1,
                "jury_id2"          => $jury_id2,
            ]);

            $affectes++;
        }

        return response()->json([
            'message'         => 'Planning généré avec succès !',
            'soutenances'     => $affectes,
            'total_etudiants' => Student::count(),
            'binomes'         => $groupes->filter(fn($g) => $g['etudiants']->count() > 1)->count(),
            'anglais'         => $groupes->filter(fn($g) => strtoupper($g['langue']) === 'EN')->count(),
            'date_debut'      => $jours[0] ?? '-',
            'date_fin'        => end($jours) ?: '-',
            'erreurs'         => $erreurs,
        ]);
    }
}