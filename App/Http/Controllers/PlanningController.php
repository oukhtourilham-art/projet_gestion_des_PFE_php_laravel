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
    $profs=Professor::all()->values(); //pas necessaier de regrouper les profs par departement car on peut affecter un prof d'un departement a un etudiant d'une filiere differente
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
    $students=Student::whereDoesntHave("Soutenance")->get(); //les etudiants sans soutenance


        $timeSlots=["9:00-10:30","14:00-15:30","16:00-17:30"]; // 3 soutenances par jour

        $Salles=["Amphi A","Salle 5 AB","Salle 4 AB","Salle 17 AB", //10 salles disponibles
        "Salle 16 AB","Salle 22 AB","Salle 23 AB",
        "Salle 24 AB","Salle 21 AB","Salle 15 AB"]; 

        $dateDebut=Carbon::parse(request('date')); //utilisation biblio carbon pour manipuler dates input par user
        $dates=[ $dateDebut->copy(),
         $dateDebut->copy()->addDay(),
          $dateDebut->copy()->addDays(2),]; //3jours de soutenance (copy car carbon manipule les objets par reference)

         $craineau= []; //pour stocker toute craineau possible (date+heure+salle) pour eviter les conflits 
          foreach($dates as $date){
        foreach($timeSlots as $time){
            foreach($Salles as $salle){
                $craineau[]=[
                    "date"=>$date->toDateString(),
                    "time"=>$time,
                    "salle"=>$salle
                ];
            }}}
            foreach($students as $index=> $student){
                
                if(empty($craineau[$index])){
                    return back()->with("error","il n'y a pas assez de craineaux pour tous les etudiants!");
                }
                 $craineaucurrent=$craineau[$index];
                 $soutenancedispo =Soutenance::where('date_soutenance', $craineaucurrent['date']) ->where('heure_debut', explode("-", $craineaucurrent['time'])[0]) ->get();

                 $profnondispo=$soutenancedispo->flatMap(function($soutenance){
    return [
        $soutenance->encadrant_id,
        $soutenance->jury_id1,
        $soutenance->jury_id2
    ];
})->filter()->unique()->values();//  ^prof   indisponibles (encadrant + jurys) pour le craineau actuel
  $profdispo = Professor::where('id','!=',$student->encadrant_id)
            ->whereNotIn('id',$profnondispo)
            ->inRandomOrder()
            ->limit(2)
            ->get();
                if($profdispo->count() < 2){
            continue; }
                    Soutenance::create([
                    "student_id"=>$student->id,
                    "date_soutenance"=>$craineaucurrent["date"],
                    "heure_debut"=>explode("-",$craineaucurrent["time"])[0],
                    "heure_fin"=>explode("-",$craineaucurrent["time"])[1],
                    "salle"=>$craineaucurrent["salle"],
                    "encadrant_id"=> $student->encadrant_id,
                    "jury_id1"=>$profdispo[0]->id,
                    "jury_id2"=>$profdispo[1]->id
                    ,]);}}}