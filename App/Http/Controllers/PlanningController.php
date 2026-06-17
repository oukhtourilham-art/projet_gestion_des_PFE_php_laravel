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
        $profs = Professor::all()->values();

        if ($profs->isEmpty()) {
            return response()->json(['error' => 'Aucun professeur trouvé !']);
        }

        $allStudents = Student::all();
        if ($allStudents->isEmpty()) {
            return response()->json(['error' => 'Aucun étudiant trouvé !']);
        }

        Student::query()->update(['encadrant_id' => null]);
        $this->allocateEncadrants($allStudents, $profs);

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

        // Auto-assign encadrants if missing
        $sansEncadrant = Student::whereNull('encadrant_id')->count();
        if ($sansEncadrant > 0) {
            $profs = Professor::all();
            if ($profs->isEmpty()) {
                return response()->json([
                    'error' => 'Aucun professeur trouvé. Veuillez d\'abord importer les professeurs.'
                ], 400);
            }

            $etudiants = Student::whereNull('encadrant_id')->get();
            $this->allocateEncadrants($etudiants, $profs);
        }

        // Vider les anciennes soutenances
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Soutenance::truncate();
        \App\Models\Jury::truncate();
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

        $profs = Professor::all();
        $juryCounts = [];
        foreach ($profs as $p) {
            $juryCounts[$p->id] = 0;
        }

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
                $profAnglais = $this->selectJuryMembers('Anglais', $profsOccupes, $juryCounts, 1)->first();

                $excludeForInfo = $profsOccupes;
                if ($profAnglais) {
                    $excludeForInfo[] = $profAnglais->id;
                }

                $profInfo = $this->selectJuryMembers('Informatique', $excludeForInfo, $juryCounts, 1)->first();

                if (!$profAnglais || !$profInfo) {
                    $jury = $this->selectJuryMembersAnyDiscipline($profsOccupes, $juryCounts, 2);
                    if ($jury->count() < 2) {
                        $erreurs[] = "Pas assez de profs pour soutenance EN : " . $groupe['etudiants']->first()->nom;
                        continue;
                    }
                    $jury_id1 = $jury[0]->id;
                    $jury_id2 = $jury[1]->id;
                } else {
                    $jury_id1 = $profAnglais->id;
                    $jury_id2 = $profInfo->id;
                }
            } else {
                $jury = $this->selectJuryMembers('Informatique', $profsOccupes, $juryCounts, 2);

                if ($jury->count() < 2) {
                    $jury = $this->selectJuryMembersAnyDiscipline($profsOccupes, $juryCounts, 2);
                }

                if ($jury->count() < 2) {
                    $erreurs[] = "Pas assez de profs pour : " . $groupe['etudiants']->first()->nom;
                    continue;
                }

                $jury_id1 = $jury[0]->id;
                $jury_id2 = $jury[1]->id;
            }

            // Track the assignment
            if ($jury_id1) {
                $juryCounts[$jury_id1] = ($juryCounts[$jury_id1] ?? 0) + 1;
            }
            if ($jury_id2) {
                $juryCounts[$jury_id2] = ($juryCounts[$jury_id2] ?? 0) + 1;
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
        $data = json_decode($response->getContent(), true);

        if (isset($data['error'])) {
            return redirect()->back()->with('error', $data['error']);
        }

        $msg = $data['message'] ?? 'Planning généré avec succès !';
        if (!empty($data['erreurs'])) {
            $msg .= ' Cependant, certaines erreurs ont été détectées : ' . implode(', ', $data['erreurs']);
            return redirect()->route('planning.index')->with('warning', $msg);
        }

        return redirect()->route('planning.index')->with('success', $msg);
    }

    private function allocateEncadrants($students, $profs)
    {
        if ($profs->isEmpty() || $students->isEmpty()) {
            return;
        }

        // Group students into projects
        $processedStudentIds = [];
        $projects = [];

        foreach ($students as $student) {
            if (in_array($student->id, $processedStudentIds)) {
                continue;
            }

            if ($student->binome == 1 && !empty($student->sujet)) {
                // Find partner
                $partner = $students->first(function ($s) use ($student, $processedStudentIds) {
                    return $s->id != $student->id &&
                           $s->binome == 1 &&
                           $s->sujet === $student->sujet &&
                           !in_array($s->id, $processedStudentIds);
                });

                if ($partner) {
                    $projects[] = [$student, $partner];
                    $processedStudentIds[] = $student->id;
                    $processedStudentIds[] = $partner->id;
                    continue;
                }
            }

            $projects[] = [$student];
            $processedStudentIds[] = $student->id;
        }

        // Sort projects: larger projects (binomes) first
        usort($projects, function ($a, $b) {
            return count($b) <=> count($a);
        });

        // Initialize prof student counts based on the database
        // excluding the students we are assigning right now
        $studentIds = $students->pluck('id')->toArray();
        $profCounts = [];
        foreach ($profs as $prof) {
            $profCounts[$prof->id] = \App\Models\Student::where('encadrant_id', $prof->id)
                ->whereNotIn('id', $studentIds)
                ->count();
        }

        // Assign each project to the professor with the lowest count who has space
        foreach ($projects as $project) {
            $projectSize = count($project);

            // Filter professors with space (current count + project size <= 4)
            $availableProfs = [];
            foreach ($profs as $prof) {
                if ($profCounts[$prof->id] + $projectSize <= 4) {
                    $availableProfs[] = $prof;
                }
            }

            if (!empty($availableProfs)) {
                // Sort by current count
                usort($availableProfs, function ($a, $b) use ($profCounts) {
                    return $profCounts[$a->id] <=> $profCounts[$b->id];
                });
                $chosenProf = $availableProfs[0];
            } else {
                // Fallback to absolute minimum count
                $sortedProfs = $profs->sortBy(function ($prof) use ($profCounts) {
                    return $profCounts[$prof->id];
                })->values();
                $chosenProf = $sortedProfs[0];
            }

            // Save assignment
            foreach ($project as $student) {
                $student->update(['encadrant_id' => $chosenProf->id]);
            }
            $profCounts[$chosenProf->id] += $projectSize;
        }
    }

    private function selectJuryMembers($discipline, $excludeIds, &$juryCounts, $limit = 1)
    {
        $candidates = Professor::where('discipline', $discipline)
            ->whereNotIn('id', $excludeIds)
            ->get();

        // Sort by current jury count to balance the load
        $candidates = $candidates->sortBy(function ($p) use ($juryCounts) {
            return $juryCounts[$p->id] ?? 0;
        })->values();

        $selected = [];
        foreach ($candidates as $candidate) {
            $currentCount = $juryCounts[$candidate->id] ?? 0;
            if ($currentCount < 4) {
                $selected[] = $candidate;
                if (count($selected) == $limit) {
                    break;
                }
            }
        }

        // Fallback: if we didn't find enough under the limit of 4, take any available candidate (sorted by count)
        if (count($selected) < $limit) {
            foreach ($candidates as $candidate) {
                if (!in_array($candidate, $selected)) {
                    $selected[] = $candidate;
                    if (count($selected) == $limit) {
                        break;
                    }
                }
            }
        }

        return collect($selected);
    }

    private function selectJuryMembersAnyDiscipline($excludeIds, &$juryCounts, $limit = 2)
    {
        $candidates = Professor::whereNotIn('id', $excludeIds)->get();

        $candidates = $candidates->sortBy(function ($p) use ($juryCounts) {
            return $juryCounts[$p->id] ?? 0;
        })->values();

        $selected = [];
        foreach ($candidates as $candidate) {
            $currentCount = $juryCounts[$candidate->id] ?? 0;
            if ($currentCount < 4) {
                $selected[] = $candidate;
                if (count($selected) == $limit) {
                    break;
                }
            }
        }

        if (count($selected) < $limit) {
            foreach ($candidates as $candidate) {
                if (!in_array($candidate, $selected)) {
                    $selected[] = $candidate;
                    if (count($selected) == $limit) {
                        break;
                    }
                }
            }
        }

        return collect($selected);
    }
}