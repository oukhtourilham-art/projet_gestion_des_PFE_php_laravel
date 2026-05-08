<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Soutenance extends Model
{
    protected $fillable = [
        'student_id',
        'date_soutenance',
        'heure_debut',
        'heure_fin',
        'salle'
    ];
    public function juries(){
        return $this->hasMany(Jury::class);
    }

    public function student(){
        return $this->belongsTo(Student::class);
    }
}
