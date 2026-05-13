<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etudiants extends Model
{
    protected $fillable = [
        'cne',
        'nom',
        'prenom',
        'email_etu',
        'email_perso',
        'filiere',
    ];
}