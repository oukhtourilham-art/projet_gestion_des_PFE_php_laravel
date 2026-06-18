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
        // Récupérer les étudiants sans encadrant
        $etudiants = Student::whereNull('encadrant_id')->get();

        if ($etudiants->isEmpty()) {
            return response()->json([
                'message' => 'Tous les étudiants ont déjà un encadrant.'
            ]);
        }

        // Récupérer tous les professeurs
        $profs = Professor::all();

        if ($profs->isEmpty()) {
            return response()->json([
                'message' => 'Aucun professeur trouvé.'
            ]);
        }

        // Distribuer équitablement
        foreach ($etudiants as $index => $etudiant) {
            $profIndex = $index % $profs->count();
            $etudiant->update([
                'encadrant_id' => $profs[$profIndex]->id
            ]);
        }

        return response()->json([
            'message'             => 'Encadrants affectés avec succès !',
            'etudiants_affectés'  => $etudiants->count(),
            'professeurs_utilisés' => $profs->count(),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function generer(){
        
        // verifion combien d'étudiants ont un encadrant
        $avecEncadrant = Student::whereNotNull('encadrant_id')->count();
        $sansEncadrant = Student::whereNull('encadrant_id')->count();

        // si des étudiants n'ont pas d'encadrant 
        if ($sansEncadrant > 0) {
            $profs = Professor::all();
            $etudiants = Student::whereNull('encadrant_id')->get();
            foreach ($etudiants as $index => $etudiant) {
                $profIndex = $index % $profs->count();
                $etudiant->update(['encadrant_id' => $profs[$profIndex]->id]);
            }
        }

        //on verifier qu'il ya des etudiant dans la BDD
        $etudiants = Student::with('encadrant')->get();

        if ($etudiants->isEmpty()){
            return response()->json([
                'message' => 'Aucun étudiant trouvé dans la base de données'
            ]);
        }

        // on créer une soutnance pour chaque étudiant
        // si elle n'existe pas deja
        foreach($etudiants as $etudiant){
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

        // pour affecter les jurys aux soutenaces sans jury
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
}