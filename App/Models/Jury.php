<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jury extends Model
{
    protected $fillable = [
        'soutenance_id',
        'professor_id',
        'role'
    ];

    public function soutenance(){
        return $this->belongsTo(Soutenance::class);
    }
}
