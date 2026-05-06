<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Professor;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Student::truncate();

        //on récupère les IDs des profs dans la BDD
        $profs = Professor::all();
        
        $etudiants = [
            // Filière TDIA:
            ['nom' => 'ACHBAB',  'prenom' => 'MOHAMMED', 'CNE' => 'M138396464',  'filiere' => 'TDAI', 'sujet_pfe' => 'IA détection fraude bancaire'],
            ['nom' => 'AFKIR',  'prenom' => 'Nada', 'CNE' => 'S139205332',  'filiere' => 'TDAI', 'sujet_pfe' => 'Transformation digitale PME'],
            ['nom' => 'AGROUAZ',  'prenom' => 'Rim', 'CNE' => 'M135463515',  'filiere' => 'TDAI', 'sujet_pfe' => 'Vision par ordinateur usine'],
            ['nom' => 'ALLALI',  'prenom' => 'Mohamed Amin', 'CNE' => 'S130003719',  'filiere' => 'TDAI', 'sujet_pfe' => 'Chatbot RH avec GPT'],
            ['nom' => 'ASSABBAR',  'prenom' => 'Malak', 'CNE' => 'N135358665',  'filiere' => 'TDAI', 'sujet_pfe' => 'Reconnaissance vocale arabe'],
            ['nom' => 'BADAOUI',  'prenom' => 'Soukaina', 'CNE' => 'M120081925',  'filiere' => 'TDAI', 'sujet_pfe' => 'Optimisation supply chain IA'],
            ['nom' => 'BADRI',  'prenom' => 'Insaf', 'CNE' => 'H130186626',  'filiere' => 'TDAI', 'sujet_pfe' => 'Maintenance prédictive IoT'],
            ['nom' => 'BOURAMTANE',  'prenom' => 'Jihane', 'CNE' => 'S130030470',  'filiere' => 'TDAI', 'sujet_pfe' => 'Deepfake detection'],

            // Filière GI:
            ['nom' => 'ABAKOUY',  'prenom' => 'Nabi', 'CNE' => 'S132170230',  'filiere' => 'GI', 'sujet_pfe' => 'Application web de gestion RH'],
            ['nom' => 'AMINE',  'prenom' => 'Issam', 'CNE' => 'N134359792',  'filiere' => 'GI', 'sujet_pfe' => 'Système de ticketing en ligne'],
            ['nom' => 'AMMARA',  'prenom' => 'Abderrahmane', 'CNE' => 'S134309419',  'filiere' => 'GI', 'sujet_pfe' => 'Plateforme e-learning Laravel'],
            ['nom' => 'AOUTTAH',  'prenom' => 'Imane', 'CNE' => 'S137056359',  'filiere' => 'GI', 'sujet_pfe' => 'API REST pour mobile'],
            ['nom' => 'AYOUB',  'prenom' => 'Mouad', 'CNE' => 'M135221806',  'filiere' => 'GI', 'sujet_pfe' => 'Application de suivi livraison'],
            ['nom' => 'AZEROUAL',  'prenom' => 'Hicham', 'CNE' => 'M138461608',  'filiere' => 'GI', 'sujet_pfe' => 'Chatbot service client'],
            ['nom' => 'AZMI',  'prenom' => 'Najib', 'CNE' => 'J149032373',  'filiere' => 'GI', 'sujet_pfe' => 'Gestion de bibliothèque'],
            ['nom' => 'BEL ASSIRI',  'prenom' => 'Fatima Zohra', 'CNE' => 'S132059401',  'filiere' => 'GI', 'sujet_pfe' => 'Système de vote en ligne'],

            // Filière DATA
            ['nom' => 'AGHZAR',  'prenom' => 'Otmane', 'CNE' => 'N138074665',  'filiere' => 'DATA', 'sujet_pfe' => 'Analyse prédictive ventes'],
            ['nom' => 'AKHIR',  'prenom' => 'Abir', 'CNE' => 'R139536313',  'filiere' => 'DATA', 'sujet_pfe' => 'Dashboard Power BI RH'],
            ['nom' => 'ALAOUI MHAMDI',  'prenom' => 'Hamza', 'CNE' => 'M130497073',  'filiere' => 'DATA', 'sujet_pfe' => 'Détection anomalies réseau'],
            ['nom' => 'ANOUK',  'prenom' => 'Zakariae', 'CNE' => 'S135236265',  'filiere' => 'DATA', 'sujet_pfe' => 'Scraping et visualisation data'],
            ['nom' => 'ATMANI',  'prenom' => 'Oumaima', 'CNE' => 'H138058875',  'filiere' => 'DATA', 'sujet_pfe' => 'Clustering clients e-commerce'],
            ['nom' => 'BAKADIRI',  'prenom' => 'Widad', 'CNE' => 'R130669967',  'filiere' => 'DATA', 'sujet_pfe' => 'NLP analyse sentiments'],
            ['nom' => 'BENALI',  'prenom' => 'Kawtar', 'CNE' => 'N134354316',  'filiere' => 'DATA', 'sujet_pfe' => 'Tableau de bord COVID'],
            ['nom' => 'BOUCHOUATA',  'prenom' => 'Hania', 'CNE' => 'G139695062',  'filiere' => 'DATA', 'sujet_pfe' => 'Recommandation films ML'],
        ];

        //on a pour chaque etudiant , on assigne un encadrant
        // olors la logique : 3 etudiant par prof (8 profs * 3 = 24 etudiants)
        foreach($etudiants as $index => $data){
            $profIndex = intdiv($index, 3); // 0,0,0 -> prof[0] | 1,1,1 -> prof[1] ...
            $encadrant = $profs[$profIndex];

            Student::create([
                'nom_complet' => $data['nom'].' '.$data['prenom'],
                'CNE' => $data['CNE'],
                'Filiere' => $data['filiere'],
                'sujet_pfe' => $data['sujet_pfe'],
                'encadrant_id' => $encadrant->id, 
            ]);
        }
    }
}
