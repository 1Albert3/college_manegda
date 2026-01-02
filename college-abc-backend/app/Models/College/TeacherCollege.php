<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * Modèle TeacherCollege
 * Enseignants officiant au Collège
 */
class TeacherCollege extends Model
{
    use HasUuids, SoftDeletes;

    protected $connection = 'school_college';
    protected $table = 'teachers_college';

    protected $fillable = [
        'user_id',
        'matricule',
        'diplomes',
        'specialites',
        'anciennete_annees',
        'date_embauche',
        'type_contrat',
        'statut',
        'heures_semaine_max',
        'observations',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
