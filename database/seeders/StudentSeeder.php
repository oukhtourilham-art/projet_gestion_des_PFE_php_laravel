<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Professor;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
         DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Student::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Pour Récupérer les profs
        $profs = Professor::all();

        if ($profs->count() == 0) {
            echo "Aucun professeur trouvé !\n";
            return;
        }

        
        $etudiants = [

            // TDIA
            ['CNE'=>'M138396464','nom'=>'ACHBAB','prenom'=>'MOHAMMED','email_perso'=>'mohammedachbab288@gmail.com','email_etu'=>'mohammed.achbab@etu.uae.ac.ma','filiere'=>'TDAI'],
            ['CNE'=>'S139205332','nom'=>'AFKIR','prenom'=>'Nada','email_perso'=>'afkirnada088@gmail.com','email_etu'=>'nada.afkir@etu.uae.ac.ma','filiere'=>'TDAI'],
            ['CNE'=>'M135463515','nom'=>'AGROUAZ','prenom'=>'Rim','email_perso'=>'rim.agrouaz15@gmail.com','email_etu'=>'rim.agrouaz@etu.uae.ac.ma','filiere'=>'TDAI'],
            ['CNE'=>'S130003719','nom'=>'ALLALI','prenom'=>'Mohamed Amin','email_perso'=>'amineallali9@gmail.com','email_etu'=>'allali.mohamedamin@etu.uae.ac.ma','filiere'=>'TDAI'],
            ['CNE'=>'N135358665','nom'=>'ASSABBAR','prenom'=>'Malak','email_perso'=>'oudmalak016@gmail.com','email_etu'=>'malak.assabbar@etu.uae.ac.ma','filiere'=>'TDAI'],
            ['CNE'=>'M120081925','nom'=>'BADAOUI','prenom'=>'Soukaina','email_perso'=>'soukainabadaoui822@gmail.com','email_etu'=>'soukaina.badaoui@etu.uae.ac.ma','filiere'=>'TDAI'],
            ['CNE'=>'H130186626','nom'=>'BADRI','prenom'=>'Insaf','email_perso'=>'insafbadri7@gmail.com','email_etu'=>'badri.insaf@etu.uae.ac.ma','filiere'=>'TDAI'],

            // GI
            ['CNE'=>'S132170230','nom'=>'ABAKOUY','prenom'=>'Nabil','email_perso'=>'nabilabakouy1@gmail.com','email_etu'=>'nabil.abakouy@etu.uae.ac.ma','filiere'=>'GI'],
            ['CNE'=>'N134359792','nom'=>'AMINE','prenom'=>'ISSAM','email_perso'=>'issamamine180@gmail.com','email_etu'=>'issam.amine@etu.uae.ac.ma','filiere'=>'GI'],
            ['CNE'=>'S134309419','nom'=>'AMMARA','prenom'=>'ABDERRAHMANE','email_perso'=>'ab.amm209@gmail.com','email_etu'=>'abderrahmane.ammara@etu.uae.ac.ma','filiere'=>'GI'],
            ['CNE'=>'S137056359','nom'=>'AOUATTAH','prenom'=>'Imane','email_perso'=>'imaneeaouattahee@gmail.com','email_etu'=>'imane.aouattah@etu.uae.ac.ma','filiere'=>'GI'],
            ['CNE'=>'M135221806','nom'=>'AYOUB','prenom'=>'Mouad','email_perso'=>'mouadayoubtaliwin@gmail.com','email_etu'=>'mouad.ayoub@etu.uae.ac.ma','filiere'=>'GI'],
            ['CNE'=>'M138461608','nom'=>'AZEROUAL','prenom'=>'Hicham','email_perso'=>'hichamazroual2002@gmail.com','email_etu'=>'hicham.azeroual@etu.uae.ac.ma','filiere'=>'GI'],
            ['CNE'=>'J149032373','nom'=>'AZMI','prenom'=>'Najib','email_perso'=>'najib.azmi2019@gmail.com','email_etu'=>'najib.azmi@etu.uae.ac.ma','filiere'=>'GI'],

            //  DATA
            ['CNE'=>'N138074665','nom'=>'AGHZAR','prenom'=>'Otmane','email_perso'=>'aghzarotmane2002@gmail.com','email_etu'=>'otmane.aghzar@etu.uae.ac.ma','filiere'=>'DATA'],
            ['CNE'=>'R139536313','nom'=>'AKHIR','prenom'=>'Abir','email_perso'=>'abirabbb00@gmail.com','email_etu'=>'abir.akhir@etu.uae.ac.ma','filiere'=>'DATA'],
            ['CNE'=>'M130497073','nom'=>'ALAOUI MHAMDI','prenom'=>'HAMZA','email_perso'=>'alaoui.hamza2002@gmail.com','email_etu'=>'hamza.alaouimhamdi@etu.uae.ac.ma','filiere'=>'DATA'],
            ['CNE'=>'S135236265','nom'=>'ANOUK','prenom'=>'Zakariae','email_perso'=>'anouk845@gmail.com','email_etu'=>'anouk.zakariae@etu.uae.ac.ma','filiere'=>'DATA'],
            ['CNE'=>'H138058875','nom'=>'ATMANI','prenom'=>'Oumaima','email_perso'=>'oumaimaamina181@gmail.com','email_etu'=>'oumaima.atmani@etu.uae.ac.ma','filiere'=>'DATA'],
            ['CNE'=>'R130669967','nom'=>'BAKADIRI','prenom'=>'Widad','email_perso'=>'wydadbakadiri2003.top@gmail.com','email_etu'=>'widad.bakadiri@etu.uae.ac.ma','filiere'=>'DATA'],
            ['CNE'=>'N134354316','nom'=>'BENALI','prenom'=>'Kawtar','email_perso'=>'benalikawtar1110@gmail.com','email_etu'=>'kawtar.benali@etu.uae.ac.ma','filiere'=>'DATA'],
        ];

       
        foreach ($etudiants as $index => $data) {

            // Distribution équitable des encadrants
            $profIndex = $index % $profs->count();
            $encadrant = $profs[$profIndex];

            Student::create([
                'CNE' => $data['CNE'],
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email_perso' => $data['email_perso'],
                'email_etu' => $data['email_etu'],
                'filiere' => $data['filiere'],
                'encadrant_id' => $encadrant->id,
            ]);
        }
    }
}