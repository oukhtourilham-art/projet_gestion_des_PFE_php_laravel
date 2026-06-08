<?php

namespace App\Services;

use App\Models\Professor;
use App\Models\Soutenance;
use App\Models\Jury;

class ConstraintChecker
{
    //1 er verification : Equilibre des jurys
    // Chaque prof ne doit pas avoir plus de 4 jurys
    public function checkEquilibre(): array
    {
        $erreurs = [];
        $profs = \App\Models\Professor::withCount(['students', 'juries'])->get();

        foreach ($profs as $prof) {

            // Vérification min/max étudiants encadrés
            if ($prof->students_count > 4) {
                $erreurs[] = "❌ " . $prof->nom . " " . $prof->prenom .
                            " encadre " . $prof->students_count .
                            " étudiants (maximum autorisé : 4).";
            }

            if ($prof->students_count > 0 && $prof->students_count < 3) {
                $erreurs[] = "⚠️ " . $prof->nom . " " . $prof->prenom .
                        " encadre seulement " . $prof->students_count .
                        " étudiant(s) (minimum recommandé : 3).";
            }

            // Vérification max jurys
            if ($prof->juries_count > 4) {
                $erreurs[] = "❌ " . $prof->nom . " " . $prof->prenom .
                         " a " . $prof->juries_count .
                         " jurys (maximum autorisé : 4).";
            }
        }

        // Vérification globale
        $totalEtudiants = \App\Models\Student::count();
        $totalProfs     = \App\Models\Professor::count();

        if ($totalProfs > 0) {
            $moyenne = round($totalEtudiants / $totalProfs, 1);
            if ($moyenne < 3) {
                $erreurs[] = "⚠️ Moyenne d'étudiants par encadrant : " . $moyenne .
                         " (minimum recommandé : 3). Vous avez trop de professeurs.";
            }
            if ($moyenne > 4) {
                $erreurs[] = "❌ Moyenne d'étudiants par encadrant : " . $moyenne .
                         " (maximum autorisé : 4). Vous n'avez pas assez de professeurs.";
            }
        }

        return $erreurs;
    }

    //2 emme verification : Chevauchement des salles
    // une salle ne peut pas avoir 2 soutenances en meme temps
    public function checkChevauchementSalles(): array
    {
        $erreurs = [];

        // On récupère toutes les soutenances qui ont une date et heure
        $soutenances = Soutenance::whereNotNull('date_soutenance')
            ->whereNotNull('heure_debut')
            ->whereNotNull('salle')
            ->orderBy('date_soutenance')
            ->orderBy('heure_debut')
            ->get();

        // On compare chaque soutenance avec les autres
        foreach ($soutenances as $s1) {
            foreach ($soutenances as $s2) {
                if ($s1->id >= $s2->id) continue; // pour éviter les doublons

                // si on a meme salle et meme date
                if ($s1->salle == $s2->salle && $s1->date_soutenance == $s2->date_soutenance) {
                    //pour vérifier chevauchement horaire
                    if ($s1->heure_debut < $s2->heure_fin && $s1->heure_fin > $s2->heure_debut) {
                        $erreurs[] = "Conflit salle {$s1->salle} le {$s1->date_soutenance} : soutenance {$s1->id} ({$s1->heure_debut}-{$s1->heure_fin}) chevauche soutenance {$s2->id} ({$s2->heure_debut}-{$s2->heure_fin})";
                    }
                }
            }
        }

        return $erreurs;
    }

    //3 emme verification : Conflits professeurs
    // un prof ne peut pas etre dans 2 soutenances en meme temps
    public function checkConflitsProfesseurs(): array
    {
        $erreurs = [];

        $profs = Professor::with(['juries.soutenance'])->get();

        foreach ($profs as $prof) {
            $jurys = $prof->juries->filter(function ($jury) {
                return $jury->soutenance &&
                       $jury->soutenance->date_soutenance &&
                       $jury->soutenance->heure_debut;
            });

            //comparer chaque jury avec les autres
            foreach ($jurys as $j1) {
                foreach ($jurys as $j2) {
                    if ($j1->id >= $j2->id) continue;

                    $s1 = $j1->soutenance;
                    $s2 = $j2->soutenance;

                    if ($s1->date_soutenance == $s2->date_soutenance) {
                        if ($s1->heure_debut < $s2->heure_fin && $s1->heure_fin > $s2->heure_debut) {
                            $erreurs[] = "Prof {$prof->nom} {$prof->prenom} est dans 2 soutenances en meme temps : soutenance {$s1->id} et {$s2->id} le {$s1->date_soutenance}";
                        }
                    }
                }
            }
        }

        return $erreurs;
    }

    //4 emme verification : Temps de repos
    // un prof doit avoir au moins 1h entre 2 soutenances
    public function checkTempsRepos(): array
    {
        $erreurs = [];

        $profs = Professor::with(['juries.soutenance'])->get();

        foreach ($profs as $prof) {
            $jurys = $prof->juries->filter(function ($jury) {
                return $jury->soutenance &&
                       $jury->soutenance->date_soutenance &&
                       $jury->soutenance->heure_fin;
            })->sortBy(function ($jury) {
                return $jury->soutenance->date_soutenance . ' ' . $jury->soutenance->heure_debut;
            });

            $jurysArray = $jurys->values();

            for ($i = 0; $i < $jurysArray->count() - 1; $i++) {
                $s1 = $jurysArray[$i]->soutenance;
                $s2 = $jurysArray[$i + 1]->soutenance;

                if ($s1->date_soutenance == $s2->date_soutenance) {
                    // Calculons le temps entre la fin de s1 et le début de s2
                    $fin    = strtotime($s1->heure_fin);
                    $debut  = strtotime($s2->heure_debut);
                    $pause  = ($debut - $fin) / 60; 

                    if ($pause < 60) {
                        $erreurs[] = "Prof {$prof->nom} {$prof->prenom} : seulement {$pause} min de pause entre soutenance {$s1->id} ({$s1->heure_fin}) et {$s2->id} ({$s2->heure_debut}) le {$s1->date_soutenance}";
                    }
                }
            }
        }

        return $erreurs;
    }

    public function checkSallesSuffisantes(): array
    {
        $erreurs = [];

        $salles = session('salles', []);
        $nbSalles = count($salles);

        if ($nbSalles == 0) {
            $erreurs[] = "❌ Aucune salle sélectionnée. Veuillez choisir les salles sur la page import.";
            return $erreurs;
        }

        // Créneaux par jour : 5 créneaux × nb salles
        $creneauxParJour = 5 * $nbSalles;

        // Jours disponibles
        $jours = session('jours_soutenance', []);
        $nbJours = count($jours);

        if ($nbJours == 0) {
            $erreurs[] = "❌ Aucune date de soutenance enregistrée. Veuillez entrer les dates sur la page import.";
            return $erreurs;
        }

        $totalCreneaux = $creneauxParJour * $nbJours;
        $totalEtudiants = \App\Models\Student::count();

        if ($totalEtudiants > $totalCreneaux) {
            $erreurs[] = "❌ Pas assez de créneaux ! " .
                     $totalEtudiants . " étudiants mais seulement " .
                     $totalCreneaux . " créneaux disponibles " .
                     "(" . $nbSalles . " salles × 5 créneaux × " . $nbJours . " jours). " .
                     "Ajoutez " . ($totalEtudiants - $totalCreneaux) . " créneau(x) supplémentaire(s).";
        } else {
            $creneauxRestants = $totalCreneaux - $totalEtudiants;
            if ($creneauxRestants < 10) {
                $erreurs[] = "⚠️ Attention : seulement " . $creneauxRestants .
                         " créneau(x) libre(s) après planification.";
            }
        }

        return $erreurs;
    }

    // La fonction principale qui lance toutes les vérifications
    public function verifierTout(): array
    {
        return [
            'salles_suffisantes'    => $this->checkSallesSuffisantes(),
            'equilibre'             => $this->checkEquilibre(),
            'chevauchement_salles'  => $this->checkChevauchementSalles(),
            'conflits_professeurs'  => $this->checkConflitsProfesseurs(),
            'temps_repos'           => $this->checkTempsRepos(),
        ];
    }
}