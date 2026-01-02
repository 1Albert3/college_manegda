<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AttendanceCollege extends Model
{
    use HasUuids;

    protected $connection = 'school_college';
    protected $table = 'attendances_college';

    protected $fillable = [
        'student_id',
        'date',
        'statut', // present, absent, retard
        'motif',
        'justifie',
        'enregistre_par'
    ];

    public function student()
    {
        return $this->belongsTo(StudentCollege::class, 'student_id');
    }
}
