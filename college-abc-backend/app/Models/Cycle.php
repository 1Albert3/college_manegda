<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    protected $fillable = ['name', 'slug'];

    public function levels()
    {
        return $this->hasMany(Level::class);
    }
}
