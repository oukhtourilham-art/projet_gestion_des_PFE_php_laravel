<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Student extends Model{
<<<<<<< HEAD
   protected $fillable = ['cne','nom','prenom','email_perso','email_etu'];
     protected $table = 'students';
=======
    protected $fillable = [
        "CNE","nom","prenom","email_perso","email_etu","filiere",
    ];

    public function encadrant(){
        return $this->belongsTo(Professor::class, 'encadrant_id');
    }
>>>>>>> ce7d28093b8d038d14b76effc7e815a3c6f07925
}
