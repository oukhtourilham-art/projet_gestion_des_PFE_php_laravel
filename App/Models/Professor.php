<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Professor extends Model
{
   protected $fillable = ['nom', 'prenom', 'discipline'];
    public function students(){
        return $this->hasMany(Student::class, 'encadrant_id');
    }

    //Relation: un prof peut etre dans plusieurs jurys
    public function juries(){
        return $this->hasMany(Jury::class, 'professor_id');
    }
}

