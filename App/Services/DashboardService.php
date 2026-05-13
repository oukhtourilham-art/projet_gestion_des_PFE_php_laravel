<?php

namespace App\Services;

use App\Models\Professor;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /*
    |
    | STAT 1 — Tableau combiné : encadrés + jurys par professeur
    |
    |
    | On utilise une seule requête SQL avec deux LEFT JOIN pour avoir
    | en une seule fois : le nom du prof, le nb d'étudiants encadrés,
    | le nb de jurys assistés.
    |
    | LEFT JOIN = "relier deux tables, garder tous les profs même s'ils
    | n'ont pas encore d'étudiants ou de jurys" (retourne 0 au lieu de rien)
    |
    | COUNT(DISTINCT s.id) = compter les étudiants UNIQUES du prof
    | DISTINCT évite de compter deux fois si un étudiant apparaît
    | dans plusieurs jointures
    |
    | AS nb_encadres = donner un nom à la colonne calculée
    |
    | GROUP BY p.id = regrouper toutes les lignes du même prof
    | pour que COUNT() fonctionne prof par prof
    |
    | ORDER BY nb_encadres DESC = trier du plus grand au plus petit
    */
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

    /*
    |==========================================================================
    | STAT 2 — Soutenances par filière (pour le diagramme cercle)
    |==========================================================================
    |
    | Student::select() = écrire une requête SELECT sur la table students
    |
    | 'filiere' = on veut la colonne filière
    |
    | DB::raw('count(*) as total') = écrire du SQL pur à l'intérieur
    | de Laravel. count(*) = compter le nombre de lignes.
    | "as total" = nommer cette colonne "total" dans le résultat
    |
    | ->groupBy('filiere') = regrouper toutes les lignes qui ont
    | la même filière, pour que count(*) compte par groupe
    |
    | ->get() = exécuter la requête et retourner tous les résultats
    */
    public function getSoutenancesParFiliere()
    {
        return Student::select('filiere', DB::raw('count(*) as total'))
            ->whereNotNull('filiere')
            ->groupBy('filiere')
            ->get();
    }

    /*
    |==========================================================================
    | STAT 3 — Chiffres résumés pour les cartes en haut
    |==========================================================================
    |
    | ::count() = méthode Eloquent qui fait SELECT COUNT(*) FROM table
    | Retourne un simple nombre entier
    |
    | On retourne un tableau associatif ['clé' => valeur]
    | pour pouvoir accéder à $stats['etudiants'] dans la vue
    */
    public function getStats()
    {
         return [
            'etudiants'   => DB::table('students')->count(),
            'profs'       => DB::table('professors')->count(),
            'soutenances' => DB::table('soutenances')->count(),
        ];
    }
}