<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Student extends Model{
   
    protected $fillable = [
        "CNE","nom","prenom","email_perso","email_etu","filiere",'encadrant_id',"sujet","langue","binome_id",
    ];

    public function encadrant(){
        return $this->belongsTo(Professor::class, 'encadrant_id');
    }

    public function soutenance(){
        return $this->hasOne(Soutenance::class);
    }
    public function binome()
    {
        return $this->belongsTo(Student::class, 'binome_id');
    }
    public function binomeOf()
    {
        return $this->hasOne(Student::class, 'binome_id');
    }
}
