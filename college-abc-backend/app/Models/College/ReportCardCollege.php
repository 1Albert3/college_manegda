<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle ReportCardCollege
 */
class ReportCardCollege extends Model
{
    use HasUuids, SoftDeletes;

    protected $connection = 'school_college';
    protected $table = 'report_cards_college';

    protected $fillable = [
        'student_id',
        'class_id',
        'school_year_id',
        'trimestre',

        'data_matieres',
        'total_points',
        'total_coefficients',
        'moyenne_generale',

        'effectif_classe',
        'moyenne_classe',
        'moyenne_premier',
        'moyenne_dernier',
        'rang',

        'is_published',
        'published_at',
    ];

    protected $casts = [
        'data_matieres' => 'array',
        'total_points' => 'float',
        'moyenne_generale' => 'float',
        'is_published' => 'boolean',
    ];
}
