<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Soutenance extends Model
{
    protected $fillable = [
        'student_id',
        'binome_student_id',
        'date_soutenance',
        'heure_debut',
        'heure_fin',
        'salle',
        'encadrant_id',  
        'jury_id1',      
        'jury_id2',      
    ];
    public function juries(){
        return $this->hasMany(Jury::class);
    }

    public function student(){
        return $this->belongsTo(Student::class);
    }
}
