<?php

namespace App\Models\Lycee;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * Modèle TeacherLycee - Enseignant Lycée
 * Base: school_lycee
 */
class TeacherLycee extends Model
{
    use HasUuids, SoftDeletes;

    protected $connection = 'school_lycee';
    protected $table = 'teachers_lycee';

    protected $fillable = [
        'user_id',
        'matricule',
        'specialite', // Matière principale
        'grade', // Grade fonction publique ?
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classesPrincipales()
    {
        return $this->hasMany(ClassLycee::class, 'prof_principal_id');
    }

    public function assignments()
    {
        return $this->hasMany(AssignmentLycee::class, 'teacher_id');
    }
}
