<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Searchable;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Modules\Core\Entities\User;

class Scholarship extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'name',
        'type',
        'percentage',
        'fixed_amount',
        'reason',
        'conditions',
        'start_date',
        'end_date',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['name', 'reason'];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'en_attente');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspendue');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expiree');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'annulee');
    }

    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByAcademicYear($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePercentageBased($query)
    {
        return $query->whereNotNull('percentage');
    }

    public function scopeFixedAmount($query)
    {
        return $query->whereNotNull('fixed_amount');
    }

    // Accessors
    public function getFormattedFixedAmountAttribute()
    {
        if (!$this->fixed_amount) return null;
        return number_format($this->fixed_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedPercentageAttribute()
    {
        if (!$this->percentage) return null;
        return number_format($this->percentage, 2) . '%';
    }

    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'bourse' => 'Bourse',
            'reduction' => 'Réduction',
            'exoneration' => 'Exonération',
            'aide_sociale' => 'Aide sociale',
            default => ucfirst($this->type),
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'en_attente' => 'En attente',
            'active' => 'Active',
            'suspendue' => 'Suspendue',
            'expiree' => 'Expirée',
            'annulee' => 'Annulée',
            default => ucfirst($this->status),
        };
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active' 
               && $this->start_date <= now() 
               && $this->end_date >= now();
    }

    public function getIsExpiredAttribute()
    {
        return $this->end_date < now();
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->is_expired) return 0;
        return now()->diffInDays($this->end_date);
    }

    // Methods
    public function approve(User $user)
    {
        $this->status = 'active';
        $this->approved_by = $user->id;
        $this->approved_at = now();
        return $this->save();
    }

    public function suspend()
    {
        $this->status = 'suspendue';
        return $this->save();
    }

    public function reactivate()
    {
        if ($this->is_expired) {
            throw new \Exception('Cannot reactivate an expired scholarship');
        }
        
        $this->status = 'active';
        return $this->save();
    }

    public function cancel()
    {
        $this->status = 'annulee';
        return $this->save();
    }

    public function expire()
    {
        $this->status = 'expiree';
        return $this->save();
    }

    public function calculateDiscountAmount(float $totalAmount)
    {
        if ($this->fixed_amount) {
            return $this->fixed_amount;
        }

        if ($this->percentage) {
            return ($totalAmount * $this->percentage / 100);
        }

        return 0;
    }

    public function applyToInvoice(Invoice $invoice)
    {
        if (!$this->is_active) {
            throw new \Exception('Scholarship is not active');
        }

        if ($invoice->student_id !== $this->student_id) {
            throw new \Exception('Scholarship does not belong to this student');
        }

        if ($invoice->academic_year_id !== $this->academic_year_id) {
            throw new \Exception('Scholarship is not valid for this academic year');
        }

        $invoice->recalculateBalance();
        return $invoice;
    }

    public function checkExpiration()
    {
        if ($this->end_date < now() && $this->status === 'active') {
            $this->expire();
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($scholarship) {
            // Recalculer les factures de l'élève pour cette année académique
            $invoices = Invoice::where('student_id', $scholarship->student_id)
                              ->where('academic_year_id', $scholarship->academic_year_id)
                              ->where('status', '!=', 'annulee')
                              ->get();

            foreach ($invoices as $invoice) {
                $invoice->recalculateBalance();
            }
        });

        static::updated(function ($scholarship) {
            if ($scholarship->wasChanged(['status', 'percentage', 'fixed_amount'])) {
                // Recalculer les factures de l'élève
                $invoices = Invoice::where('student_id', $scholarship->student_id)
                                  ->where('academic_year_id', $scholarship->academic_year_id)
                                  ->where('status', '!=', 'annulee')
                                  ->get();

                foreach ($invoices as $invoice) {
                    $invoice->recalculateBalance();
                }
            }
        });
    }
}
