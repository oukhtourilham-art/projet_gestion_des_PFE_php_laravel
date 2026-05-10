<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JurySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        | Chaque soutenance a un jury de 3 profs.
        | On simule 10 soutenances avec des affectations fictives.
        | soutenance_id = identifiant de la soutenance
        | professor_id  = identifiant du prof dans professors
        | role          = président, examinateur, ou encadrant
        */
        DB::table('juries')->insert([
            ['soutenance_id' => 1, 'professor_id' => 2, 'role' => 'président'],
            ['soutenance_id' => 1, 'professor_id' => 3, 'role' => 'examinateur'],
            ['soutenance_id' => 2, 'professor_id' => 1, 'role' => 'président'],
            ['soutenance_id' => 2, 'professor_id' => 4, 'role' => 'examinateur'],
            ['soutenance_id' => 3, 'professor_id' => 5, 'role' => 'président'],
            ['soutenance_id' => 3, 'professor_id' => 2, 'role' => 'examinateur'],
            ['soutenance_id' => 4, 'professor_id' => 6, 'role' => 'président'],
            ['soutenance_id' => 4, 'professor_id' => 3, 'role' => 'examinateur'],
            ['soutenance_id' => 5, 'professor_id' => 1, 'role' => 'président'],
            ['soutenance_id' => 5, 'professor_id' => 7, 'role' => 'examinateur'],
        ]);
    }
}
