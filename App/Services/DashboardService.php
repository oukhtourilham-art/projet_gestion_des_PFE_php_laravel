<?php

namespace App\Services;

use App\Models\Professor;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getTableauCombine()
    {
        return DB::table('professors as p')
            ->select([
                DB::raw("CONCAT(p.nom, ' ', p.prenom) as nom_complet"),
                DB::raw('COUNT(DISTINCT s.id) as nb_encadres'),
                DB::raw('COUNT(DISTINCT j.id) as nb_jurys'),
            ])
            ->leftJoin('students as s', 's.encadrant_id', '=', 'p.id')
            ->leftJoin('juries as j', 'j.professor_id', '=', 'p.id')
            ->groupBy('p.id', 'p.nom','p.prenom')
            ->orderByDesc('nb_encadres')
            ->get();
    }

    public function getSoutenancesParFiliere()
    {
        return Student::select('filiere', DB::raw('count(*) as total'))
            ->whereNotNull('filiere')
            ->groupBy('filiere')
            ->get();
    }

    public function getStats()
    {
         return [
            'etudiants'   => DB::table('students')->count(),
            'profs'       => DB::table('professors')->count(),
            'soutenances' => DB::table('soutenances')->count(),
        ];
    }

    
// DÉTECTION DES ANOMALIES
// Vérifie 3 contraintes et retourne la liste des problèmes

public function getAnomalies()
{
    $anomalies = []; // tableau vide au départ — on y ajoute les problèmes trouvés

    
    $encadrements = DB::table('students')
        ->select('encadrant_id', DB::raw('COUNT(*) as nb_total'),
                DB::raw('SUM(CASE WHEN binome = 1 THEN 1 ELSE 0 END) as nb_binome'))
        ->whereNotNull('encadrant_id')
        ->groupBy('encadrant_id')
        ->get();

    foreach ($encadrements as $enc) {
        // Nombre de groupes = solos + (binômes / 2 arrondi)
        $nbSolos   = $enc->nb_total - $enc->nb_binome;
        $nbGroupes = $nbSolos + (int) ceil($enc->nb_binome / 2);

        if ($nbGroupes < 3 || $nbGroupes > 4) {
            $prof = DB::table('professors')->where('id', $enc->encadrant_id)->first();
            $nom  = $prof ? $prof->nom . ' ' . $prof->prenom : 'Prof ID ' . $enc->encadrant_id;

            $anomalies[] = [
                'type'    => 'encadrement',
                'niveau'  => $nbGroupes > 4 ? 'danger' : 'warning',
                'message' => $nom . ' encadre ' . $nbGroupes . ' groupe(s) (étudiants : ' . $enc->nb_total . ')'
                        . ($nbGroupes > 4 ? ' (max autorisé : 4)' : ' (min requis : 3)'),
            ];
        }
    }

    // ANOMALIE 2 : chevauchement de salles 
    // Deux soutenances dans la même salle au même créneau = conflit
    $chevauchements = DB::table('soutenances as s1')
        ->join('soutenances as s2', function($join) {

            $join->on('s1.salle', '=', 's2.salle')                        // même salle
                 ->on('s1.date_soutenance', '=', 's2.date_soutenance')    // même jour
                 ->on('s1.heure_debut', '=', 's2.heure_debut')            // même heure
                 ->whereColumn('s1.id', '<', 's2.id');                    // évite les doublons
        })
        ->select('s1.salle', 's1.date_soutenance', 's1.heure_debut')
        ->get();

    foreach ($chevauchements as $c) {
        $anomalies[] = [
            'type'    => 'planning',
            'niveau'  => 'danger',
            'message' => 'Chevauchement salle ' . $c->salle
                       . ' le ' . $c->date_soutenance
                       . ' à ' . $c->heure_debut,
        ];
    }

    //  ANOMALIE 3 : prof dans 2 soutenances au même créneau
    // Un prof ne peut pas être dans 2 salles en même temps
    $conflits = DB::table('juries as j1')
        ->join('juries as j2', function($join) {
            $join->on('j1.professor_id', '=', 'j2.professor_id') // même prof
                 ->whereColumn('j1.id', '<', 'j2.id');            // évite doublons
        })
        ->join('soutenances as s1', 's1.id', '=', 'j1.soutenance_id')
        ->join('soutenances as s2', 's2.id', '=', 'j2.soutenance_id')
        ->whereColumn('s1.date_soutenance', '=', 's2.date_soutenance')
        ->whereColumn('s1.heure_debut', '=', 's2.heure_debut')
        ->select('j1.professor_id', 's1.date_soutenance', 's1.heure_debut')
        ->get();

    foreach ($conflits as $conf) {
        $prof = DB::table('professors')->where('id', $conf->professor_id)->first();
        $nom  = $prof ? $prof->nom . ' ' . $prof->prenom : 'Prof ID ' . $conf->professor_id;

        $anomalies[] = [
            'type'    => 'planning',
            'niveau'  => 'danger',
            'message' => $nom . ' est dans 2 soutenances le '
                       . $conf->date_soutenance . ' à ' . $conf->heure_debut,
        ];
    }

    //  ANOMALIE 4 : moins d'1h de repos entre 2 soutenances du même prof 
    $tousJurys = DB::table('juries as j')
        ->join('soutenances as s', 's.id', '=', 'j.soutenance_id')
        ->select('j.professor_id', 's.date_soutenance', 's.heure_debut', 's.heure_fin')
        ->orderBy('j.professor_id')
        ->orderBy('s.date_soutenance')
        ->orderBy('s.heure_debut')
        ->get()
        ->groupBy('professor_id'); // groupe par prof

    foreach ($tousJurys as $profId => $soutenances) {
        $liste = $soutenances->values(); // reindexe le tableau
        for ($i = 0; $i < count($liste) - 1; $i++) {
            $fin     = strtotime($liste[$i]->heure_fin);       // heure fin soutenance i
            $debut   = strtotime($liste[$i+1]->heure_debut);   // heure début soutenance suivante
            $repos   = ($debut - $fin) / 60;                   // différence en minutes

            // Si même jour et moins de 60 minutes de pause
            if ($liste[$i]->date_soutenance === $liste[$i+1]->date_soutenance && $repos < 60) {
                $prof = DB::table('professors')->where('id', $profId)->first();
                $nom  = $prof ? $prof->nom . ' ' . $prof->prenom : 'Prof ID ' . $profId;

                $anomalies[] = [
                    'type'    => 'repos',
                    'niveau'  => 'warning',
                    'message' => $nom . ' : seulement ' . $repos
                               . ' min de pause entre 2 soutenances le ' . $liste[$i]->date_soutenance,
                ];
            }
        }
    }

    return $anomalies; // retourne le tableau complet (vide si tout est OK)
}
}