<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PlanningController;
use Illuminate\Http\Request;

use App\Models\Soutenance;
use App\Models\Professor;
use App\Models\Jury;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class AffectationController extends Controller
{
    public function affecterEncadrants()
    {
        $profs = Professor::all();

        if ($profs->isEmpty()) {
            return response()->json([
                'message' => 'Aucun professeur trouvé.'
            ]);
        }

        $allStudents = Student::all();
        if ($allStudents->isEmpty()) {
            return response()->json([
                'message' => 'Aucun étudiant trouvé.'
            ]);
        }

        $this->allocateEncadrants($allStudents, $profs);

        return response()->json([
            'message'             => 'Encadrants affectés avec succès !',
            'etudiants_affectés'  => $allStudents->count(),
            'professeurs_utilisés' => $profs->count(),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function generer(){

        $profs = Professor::all();
        $allStudents = Student::all();

        if ($allStudents->isEmpty()){
            return response()->json([
                'message' => 'Aucun étudiant trouvé dans la base de données'
            ]);
        }

        if ($profs->isEmpty()){
            return response()->json([
                'message' => 'Aucun professeur trouvé dans la base de données'
            ]);
        }

        $this->allocateEncadrants($allStudents, $profs);

        // Create soutenances for students without one
        foreach($allStudents as $etudiant){
            $dejaExiste = Soutenance::where('student_id', $etudiant->id)->exists();

            if(!$dejaExiste){
                Soutenance::create([
                    'student_id' => $etudiant->id,
                    'date_soutenance' => null,
                    'heure_debut' => null,
                    'heure_fin' => null,
                    'salle' => null,
                ]);
            }
        }

        // Assign juries to soutenances without them
        $soutenances = Soutenance::doesntHave('juries')->with('student.encadrant')->get();

        $affectees = 0;
        $erreurs = [];

        foreach($soutenances as $soutenance){

            //verfion que l'etudiant a un encadrant
            if(!$soutenance->student || !$soutenance->student->encadrant){
                $erreurs[] = "Etudiant ID {$soutenance->student_id} n'a pas d'encadrant";
                continue;
            }

            $encadrant = $soutenance->student->encadrant;

            //on récupérer tous les profs sauf l'encadarant
            $profs = Professor::where('id', '!=', $encadrant->id)->get();

            //trie par nombre de jurys (les mois chargés en premier)
            $profs = $profs->sortBy(function($prof){
                return $prof->juries()->count();
            });

            //on prendre les 2 premier qui est moins chargés
            $juryMembers = $profs->take(2);

            if($juryMembers->count() < 2){
                $erreurs[] = "Pas assez de professeurs disponibles pour la soutenance ID {$soutenance->id}";
                continue;
            }

            // on cree les 2 membres du jury
            foreach($juryMembers as $index => $prof){
                Jury::create([
                    'soutenance_id' => $soutenance->id,
                    'professor_id'  => $prof->id,
                    'role' => $index == 0 ? 'president' : 'examinateur',
                ]);
            }

            $affectees++;
        }

        // Retourner un rapport
        return response()->json([
            'message'  => 'Affectation terminée avec succès !',
            'soutenances_créées'  => Soutenance::count(),
            'jurys_affectés'    => $affectees,
            'erreurs'           => $erreurs,
        ]);
    }

    public function genererTout()
    {
        //Affecter les encadrants
        $this->affecterEncadrants();

        // Générer le planning
        app(PlanningController::class)->generatePlanning();

        return response()->json([
            'message' => 'Affectation et planning générés avec succès !',
            'etudiants' => \App\Models\Student::count(),
            'soutenances' => \App\Models\Soutenance::count(),
        ]);
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
}
