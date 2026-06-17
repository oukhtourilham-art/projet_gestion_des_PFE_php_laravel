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
            'date_debut'             => 'required|date',
            'date_fin'               => 'required|date|after_or_equal:date_debut',
            'start_time'             => 'nullable',
            'end_time'               => 'nullable',
            'slot_duration_minutes'  => 'nullable|integer|min:30',
            'break_duration_minutes' => 'nullable|integer|min:0',
        ]);

        $debut = Carbon::parse($request->date_debut);
        $fin   = Carbon::parse($request->date_fin);

        $jours = [];
        $current = $debut->copy();
        while ($current->lte($fin)) {
            $jours[] = $current->toDateString();
            $current->addDay();
        }

        // Calcul du nombre de créneaux par jour
        $startTime    = $request->input('start_time', '09:00');
        $endTime      = $request->input('end_time', '17:00');
        $slotDuration = (int) $request->input('slot_duration_minutes', 60);
        $breakDuration= (int) $request->input('break_duration_minutes', 0);

        $startMinutes = (int) explode(':', $startTime)[0] * 60 + (int) explode(':', $startTime)[1];
        $endMinutes   = (int) explode(':', $endTime)[0] * 60   + (int) explode(':', $endTime)[1];
        $totalMinutes = $endMinutes - $startMinutes;
        $slotTotal    = $slotDuration + $breakDuration;
        $nbCreneaux   = $slotTotal > 0 ? (int) floor($totalMinutes / $slotTotal) : 4;

        // Générer les créneaux réels pour le planning
        $timeSlots = [];
        $current = $startMinutes;
        while (($current + $slotDuration) <= $endMinutes) {
            $debutH = sprintf('%02d:%02d', intdiv($current, 60), $current % 60);
            $finH   = sprintf('%02d:%02d', intdiv($current + $slotDuration, 60), ($current + $slotDuration) % 60);
            $timeSlots[] = ['debut' => $debutH, 'fin' => $finH];
            $current += $slotDuration + $breakDuration;
        }

        session([
            'date_debut'       => $request->date_debut,
            'date_fin'         => $request->date_fin,
            'jours_soutenance' => $jours,
            'nb_creneaux'      => $nbCreneaux,
            'time_slots'       => $timeSlots,   // utilisé par generatePlanning
        ]);

        return redirect()->back()->with('success',
            'Dates enregistrées : ' . count($jours) . ' jour(s) du ' .
            $debut->format('d/m/Y') . ' au ' . $fin->format('d/m/Y') .
            ' — ' . $nbCreneaux . ' créneau(x)/jour'
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
        // Validate all required session data exists
        $jours = session('jours_soutenance', []);
        $timeSlots = session('time_slots', []);
        $salles = session('salles', []);
        $nbCreneaux = session('nb_creneaux');

        if (empty($jours)) {
            return response()->json([
                'error' => 'Dates de soutenance non configurées. Veuillez d\'abord enregistrer les dates.'
            ], 400);
        }

        if ($nbCreneaux === null || $nbCreneaux == 0) {
            return response()->json([
                'error' => 'Nombre de créneaux non configuré. Veuillez d\'abord configurer les dates avec des créneaux.'
            ], 400);
        }

        if (empty($timeSlots)) {
            return response()->json([
                'error' => 'Créneaux horaires non calculés. Veuillez d\'abord configurer les dates.'
            ], 400);
        }

        if (empty($salles)) {
            return response()->json([
                'error' => 'Salles non configurées. Veuillez d\'abord sélectionner les salles.'
            ], 400);
        }

        if (\App\Models\Student::count() == 0) {
            return response()->json([
                'error' => 'Aucun étudiant trouvé. Veuillez d\'abord importer les étudiants.'
            ], 400);
        }

        // Balance encadrant distribution across all professors
        $profs = Professor::all();
        if ($profs->isEmpty()) {
            return response()->json([
                'error' => 'Aucun professeur trouvé. Veuillez d\'abord importer les professeurs.'
            ], 400);
        }

        if ($profs->count() > 0) {
            // Distribute all students evenly across professors
            $allStudents = Student::all();
            foreach ($allStudents as $index => $student) {
                $profIndex = $index % $profs->count();
                $student->update(['encadrant_id' => $profs[$profIndex]->id]);
            }
        }

        // Vider les anciennes soutenances
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Soutenance::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    
        $binomesTraites = [];
        $groupes = collect();

    
        $filieres = Student::whereNotNull('filiere')
            ->distinct()
            ->pluck('filiere')
            ->values();

        $groupesParFiliere = $filieres->mapWithKeys(function ($f) {
            return [$f => Student::where('filiere', $f)->get()->shuffle()];
        });

        $max = $groupesParFiliere->map->count()->max() ?? 0;
        $studentsMelanges = collect();

        for ($i = 0; $i < $max; $i++) {
            foreach ($groupesParFiliere as $groupe) {
                if ($groupe->has($i)) {
                    $studentsMelanges->push($groupe[$i]);
                }
            }
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

                        // LOG pour verifier
                        \Log::info('Binôme détecté: ' . $student->nom . ' + ' . $partner->nom);
                        continue;
                    }
                }

                // etudiant seul
                $groupes->push([
                    'etudiants'    => collect([$student]),
                    'langue'       => $student->langue ?? 'FR',
                    'encadrant_id' => $student->encadrant_id,
                ]);
                $binomesTraites[] = $student->id;
        }

        // ETAPE 2 : Creneaux
        $timeSlots = session('time_slots', [
            ["debut" => "09:00", "fin" => "10:00"],
            ["debut" => "11:00", "fin" => "12:00"],
            ["debut" => "14:00", "fin" => "15:00"],
            ["debut" => "16:00", "fin" => "17:00"],
        ]);

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

        // ETAPE 3 : Assigner un creneau pour chaque groupe
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

            // Profs occupees dans ce creneau
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

            $soutenance = Soutenance::create([
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

            // Create Jury records for the planning view
            if ($jury_id1) {
                \App\Models\Jury::create([
                    'soutenance_id' => $soutenance->id,
                    'professor_id'  => $jury_id1,
                    'role'          => 'president',
                ]);
            }

            if ($jury_id2) {
                \App\Models\Jury::create([
                    'soutenance_id' => $soutenance->id,
                    'professor_id'  => $jury_id2,
                    'role'          => 'examinateur',
                ]);
            }

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

    public function generatePlanningWeb()
    {
        $response = $this->generatePlanning();
        $data = $response->getData(true);

        if (isset($data['error'])) {
            return redirect()->back()->with('error', '❌ ' . $data['error']);
        }

        $msg = '✅ ' . ($data['soutenances'] ?? 0) . ' soutenances générées ! ';
        if (($data['binomes'] ?? 0) > 0) {
            $msg .= '(' . $data['binomes'] . ' binômes)';
        }

        if (!empty($data['erreurs'])) {
            $msg .= ' ⚠️ ' . count($data['erreurs']) . ' erreur(s) détectée(s)';
            return redirect()->back()->with('warning', $msg)
                ->with('erreurs_details', $data['erreurs']);
        }

        return redirect()->route('planning.index')->with('success', $msg);
}}