<?php

namespace Modules\Student\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'type',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'issue_date',
        'expiry_date',
        'description',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now())->whereNotNull('expiry_date');
    }

    public function scopeValid($query)
    {
        return $query->where(function($q) {
            $q->where('expiry_date', '>=', now())
              ->orWhereNull('expiry_date');
        });
    }

    // Accessors
    public function getFileSizeHumanAttribute()
    {
        if (!$this->file_size) return 'N/A';
        
        $kb = $this->file_size;
        if ($kb < 1024) return $kb . ' KB';
        return round($kb / 1024, 2) . ' MB';
    }

    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getTypeLabel Attribute()
    {
        $labels = [
            'birth_certificate' => 'Acte de naissance',
            'medical_certificate' => 'Certificat médical',
            'photo' => 'Photo',
            'transcript' => 'Relevé de notes',
            'other' => 'Autre',
        ];

        return $labels[$this->type] ?? $this->type;
    }
}
