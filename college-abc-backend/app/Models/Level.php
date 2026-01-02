<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $fillable = ['cycle_id', 'name', 'code'];

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }
}
