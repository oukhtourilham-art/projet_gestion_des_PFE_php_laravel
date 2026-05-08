<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Student extends Model{
   protected $fillable = ['cne','nom','prenom','email_perso','email_etu'];
     protected $table = 'students';
}
