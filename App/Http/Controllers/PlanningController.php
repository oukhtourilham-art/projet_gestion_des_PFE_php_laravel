<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Affectation;
use App\Models\Soutenance;
use App\Models\Professor;
use App\Models\Jury;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use  phpOffice\phpWord\PhpWord;

class PlanningController extends Controller{
    public function generateAffectation(){
    Student::query()->update(['encadrant_id' => null]); //etudiants sans encadrant}
    $studentsByFiliere=Student::all()->groupBy('filiere');   //regrouper par filire
    $profs=Professor::all()->values(); //pas necessaier de regrouper les profs par departement car on peut affecter un prof d'un departement a un etudiant d'une filiere differente, ici values() c'est pour réorganise les indixe : [0 => prof1, 1 => prof2]
    if($profs->isEmpty()){
        return back()->with("error","il y a pas des profs pour affectation!");
    } foreach($studentsByFiliere as $filiere=>$students){
        $i=0;
        foreach($students as $student){
            $student->encadrant_id=$profs[$i%count($profs)]->id; //affecter le prof en utilisant le modulo pour faire le tour de la liste des profs
            $student->save();
        
            $i++;}
        }
     }
 

  public function generatePlanning(){

    $students = Student::whereDoesntHave("soutenance")->get(); // students sans soutenance

    $timeSlots = ["9:00-10:30","14:00-15:30","16:00-17:30"]; // 3 slots

    $salles = [
        "Amphi A","Salle 5 AB","Salle 4 AB","Salle 17 AB",
        "Salle 16 AB","Salle 22 AB","Salle 23 AB",
        "Salle 24 AB","Salle 21 AB","Salle 15 AB"
    ]; // salles dispo

    $dateDebut = Carbon::parse(request('date')); // date input user

    $dates = [
        $dateDebut->copy(),
        $dateDebut->copy()->addDay(),
        $dateDebut->copy()->addDays(2),
    ]; // 3 jours

    $creneaux = []; // tous les slots possibles
    foreach($dates as $date){
        foreach($timeSlots as $time){
              foreach($salles as $salle){
                $creneaux[] = [
                    "date" => $date->toDateString(),
                    "time" => $time,
                    "salle" => $salle
                ];
            }
        }
    }

    foreach($students as $index => $student){
        if(!isset($creneaux[$index])){
            return back()->with("error","Pas assez de créneaux !"); }
        $creneau = $creneaux[$index];

      [$heureDebut,$heureFin] = explode("-",$creneau['time']);

        // profs occupés ce créneau
        $soutenances = Soutenance::where('date_soutenance',$creneau['date'])
            ->where('heure_debut',$heureDebut)
            ->get();
        $profsOccupes = $soutenances->flatMap(function($s){
            return [
                $s->encadrant_id,
                $s->jury_id1,
                $s->jury_id2
            ];
        })->filter()->unique();
        // langue etudiant
        $langueEN = strtoupper($student->langue ?? 'FR') === "EN";
        $queryBase = Professor::where('discipline','INFORMATIQUE')
            ->where('id','!=',$student->encadrant_id)
            ->whereNotIn('id',$profsOccupes);
        if($langueEN){
            // chercher prof anglais
            $prof1 = (clone $queryBase)
                ->where('$discipline','EN')
                ->inRandomOrder()
                ->first();
            if(!$prof1) continue;

            // 2eme prof
            $prof2 = (clone $queryBase)
                ->where('id','!=',$prof1->id)
                ->inRandomOrder()
                ->first();
            if(!$prof2) continue;
            $jury = collect([$prof1,$prof2]);
        }else{
            $jury = (clone $queryBase)
                ->where(function($q){
                    $q->where('langue','FR')
                      ->orWhereNull('langue'); })
                ->inRandomOrder()
                ->limit(2)
                ->get();

            if($jury->count() < 2) continue;
        }

        Soutenance::create([
            "student_id" => $student->id,
            "date_soutenance" => $creneau["date"],
            "heure_debut" => $heureDebut,
            "heure_fin" => $heureFin,
            "salle" => $creneau["salle"],
            "encadrant_id" => $student->encadrant_id,
            "jury_id1" => $jury[0]->id,
            "jury_id2" => $jury[1]->id
        ]);
    }
}}