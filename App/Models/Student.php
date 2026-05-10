<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Student extends Model{
    protected $fillable = [
        "CNE","nom","prenom","email_perso","email_etu","filiere"
    ];

    public function encadrant(){
        return $this->belongsTo(Professor::class, 'encadrant_id');
    }
}
