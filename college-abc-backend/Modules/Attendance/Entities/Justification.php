<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuid;
use Modules\Core\Entities\User;

class Justification extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'attendance_id',
        'type',
        'reason',
        'description',
        'documents',
        'medical_certificate_path',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'approval_notes',
        'metadata',
        'admin_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'documents' => 'array',
        'metadata' => 'array',
    ];

    // Relations
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'medical_certificate' => 'Certificat médical',
            'parental_note' => 'Motif parental',
            'administrative' => 'Justificatif administratif',
            'other' => 'Autre',
            default => 'Inconnu',
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'under_review' => 'En révision',
            default => 'Inconnu',
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'under_review' => 'info',
            default => 'light',
        };
    }

    // Methods
    public function approve($approvedBy, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        // Marquer l'absence comme justifiée
        $this->attendance->update(['justified' => true]);
    }

    public function reject($approvedBy, $notes = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    public function markUnderReview($notes = null)
    {
        $this->update([
            'status' => 'under_review',
            'admin_notes' => $notes,
        ]);
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function canBeReviewed()
    {
        return in_array($this->status, ['pending', 'under_review']);
    }

    public function addDocument($path, $type = 'general')
    {
        $documents = $this->documents ?? [];
        $documents[] = [
            'path' => $path,
            'type' => $type,
            'uploaded_at' => now()->toISOString(),
        ];

        $this->update(['documents' => $documents]);
    }

    public function removeDocument($index)
    {
        $documents = $this->documents ?? [];
        if (isset($documents[$index])) {
            unset($documents[$index]);
            $this->update(['documents' => array_values($documents)]);
        }
    }
}
