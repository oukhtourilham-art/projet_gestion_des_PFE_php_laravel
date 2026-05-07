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

        $profs = Professor::withCount('juries')->get();

        foreach ($profs as $prof) {
            if ($prof->juries_count > 4) {
                $erreurs[] = "{$prof->nom} {$prof->prenom} a {$prof->juries_count} jurys (maximum autorisé : 4)";
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
                if ($s1->salle == $s2->salle && $s1->date == $s2->date) {
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

            // Comparer chaque jury avec les autres
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

    // La fonction principale qui lance toutes les vérifications
    public function verifierTout(): array
    {
        return [
            'equilibre'             => $this->checkEquilibre(),
            'chevauchement_salles'  => $this->checkChevauchementSalles(),
            'conflits_professeurs'  => $this->checkConflitsProfesseurs(),
            'temps_repos'           => $this->checkTempsRepos(),
        ];
    }
}