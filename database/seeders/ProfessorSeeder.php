<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Professor;
use Illuminate\Support\Facades\DB;

class ProfessorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       // Désactiver temporairement les contraintes pour pouvoir vider les tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // On efface d'abord les anciens profs pour éviter les doublons
        Professor::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // pur réactiver les rolations

        $professors = [
            ['nom' => 'ABAKOUY',  'prenom' => 'Redouan',  'discipline' => 'Informatique'],
            ['nom' => 'ABOUELHANOUNE',  'prenom' => 'Younes',  'discipline' => 'Mathématique'],
            ['nom' => 'ADDAM',  'prenom' => 'Mohamed',  'discipline' => 'Mathématique'],
            ['nom' => 'ADDOU',  'prenom' => 'Khadija',  'discipline' => 'Gestion'],
            ['nom' => 'ALLAOUZI',  'prenom' => 'Imane',  'discipline' => 'Informatique'],
            ['nom' => 'AMATTOUCH ',  'prenom' => 'Mohamed Ridouan',  'discipline' => 'Mathématique'],
            ['nom' => 'BADI',  'prenom' => 'Imad',  'discipline' => 'Informatique'],
            ['nom' => 'BAHRI',  'prenom' => 'Abdelkhalak',  'discipline' => 'Informatique'],
        ];

        foreach($professors as $prof){
            Professor::create([
                'nom' => $prof['nom'],
                'prenom' => $prof['prenom'],
                'discipline' => $prof['discipline'],
            ]);
        }
    }
}
