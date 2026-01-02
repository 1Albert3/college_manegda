<?php

namespace App\Models\MP;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle AttendanceMP - Gestion des absences/retards Maternelle/Primaire
 * Base: school_mp
 */
class AttendanceMP extends Model
{
    use HasUuids, SoftDeletes;

    protected $connection = 'school_mp';
    protected $table = 'attendances_mp';

    protected $fillable = [
        'student_id',
        'class_id',
        'school_year_id',
        'subject_id', // Optionnel pour le primaire (absences souvent à la journée/demi-journée)
        'date',
        'heure_debut',
        'heure_fin',
        'type', // 'absence', 'retard'
        'statut', // 'justifiee', 'non_justifiee'
        'motif',
        'justificatif_path',
        'recorded_by' // User ID (enseignant ou vie scolaire)
    ];

    protected $casts = [
        'date' => 'date',
        'heure_debut' => 'datetime',
        'heure_fin' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(StudentMP::class, 'student_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassMP::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectMP::class, 'subject_id');
    }

    // Scopes utiles pour les bulletins
    public function scopeAbsences($query)
    {
        return $query->where('type', 'absence');
    }

    public function scopeRetards($query)
    {
        return $query->where('type', 'retard');
    }

    public function scopeJustifiees($query)
    {
        return $query->where('statut', 'justifiee');
    }
}
