<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Student extends Model{
<<<<<<< Updated upstream
   
    protected $fillable = [
        "CNE","nom","prenom","email_perso","email_etu","filiere",'encadrant_id',
    ];

    public function encadrant(){
        return $this->belongsTo(Professor::class, 'encadrant_id');
    }
=======
   protected $fillable = ['cne', 'nom', 'prenom', 'email_perso', 'email_etu', 'filiere'];
     protected $table = 'students';
>>>>>>> Stashed changes
}
