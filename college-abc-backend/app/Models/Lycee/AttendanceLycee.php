<?php

namespace App\Models\Lycee;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AttendanceLycee extends Model
{
    use HasUuids;

    protected $connection = 'school_lycee';
    protected $table = 'attendances_lycee';

    protected $fillable = [
        'student_id',
        'date',
        'statut',
        'motif',
        'justifie',
        'enregistre_par'
    ];

    public function student()
    {
        return $this->belongsTo(StudentLycee::class, 'student_id');
    }
}
