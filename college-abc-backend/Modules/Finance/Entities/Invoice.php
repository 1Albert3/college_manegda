<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Searchable;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Modules\Core\Entities\User;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'academic_year_id',
        'period',
        'total_amount',
        'discount_amount',
        'paid_amount',
        'due_amount',
        'due_date',
        'issue_date',
        'status',
        'notes',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'due_date' => 'date',
        'issue_date' => 'date',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['invoice_number'];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function feeTypes()
    {
        return $this->belongsToMany(FeeType::class, 'invoice_fee_types')
                    ->withPivot('base_amount', 'discount_amount', 'final_amount', 'quantity')
                    ->withTimestamps();
    }

    public function reminders()
    {
        return $this->hasMany(PaymentReminder::class);
    }

    public function scholarships()
    {
        return $this->hasMany(Scholarship::class, 'student_id', 'student_id')
                    ->where('academic_year_id', $this->academic_year_id)
                    ->where('status', 'active');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'brouillon');
    }

    public function scopeIssued($query)
    {
        return $query->where('status', 'emise');
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('status', 'partiellement_payee');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'payee');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'en_retard');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['emise', 'partiellement_payee', 'en_retard']);
    }

    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByAcademicYear($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('due_date', '<=', now()->addDays($days))
                     ->where('due_date', '>=', now())
                     ->whereIn('status', ['emise', 'partiellement_payee']);
    }

    public function scopeOrderByRecent($query)
    {
        return $query->orderBy('issue_date', 'desc');
    }

    // Accessors
    public function getFormattedTotalAmountAttribute()
    {
        return number_format($this->total_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedPaidAmountAttribute()
    {
        return number_format($this->paid_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getFormattedDueAmountAttribute()
    {
        return number_format($this->due_amount, 0, ',', ' ') . ' FCFA';
    }

    public function getPaymentProgressAttribute()
    {
        if ($this->total_amount == 0) return 0;
        return round(($this->paid_amount / $this->total_amount) * 100, 2);
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date < now() && !$this->is_paid;
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'payee';
    }

    public function getIsPartiallyPaidAttribute()
    {
        return $this->status === 'partiellement_payee';
    }

    public function getDaysUntilDueAttribute()
    {
        return now()->diffInDays($this->due_date, false);
    }

    // Methods
    public static function generateInvoiceNumber()
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return sprintf('INV%s%06d', $year, $count);
    }

    public function recalculateBalance()
    {
        // Recalculer le montant total à partir des lignes de frais
        $totalFromFees = $this->feeTypes()->sum('invoice_fee_types.final_amount');
        
        if ($totalFromFees > 0) {
            $this->total_amount = $totalFromFees;
        }

        // Calculer les réductions totales à partir des bourses actives
        $scholarshipDiscount = $this->calculateScholarshipDiscount();
        $this->discount_amount = $scholarshipDiscount;

        // Calculer le montant payé à partir des paiements validés
        $this->paid_amount = Payment::where('student_id', $this->student_id)
                                    ->where('academic_year_id', $this->academic_year_id)
                                    ->validated()
                                    ->sum('amount');

        // Calculer le montant dû
        $this->due_amount = ($this->total_amount - $this->discount_amount) - $this->paid_amount;

        // Mettre à jour le statut
        $this->updateStatus();

        return $this->save();
    }

    public function calculateScholarshipDiscount()
    {
        $totalDiscount = 0;
        
        $scholarships = Scholarship::where('student_id', $this->student_id)
                                  ->where('academic_year_id', $this->academic_year_id)
                                  ->where('status', 'active')
                                  ->where('start_date', '<=', now())
                                  ->where('end_date', '>=', now())
                                  ->get();

        foreach ($scholarships as $scholarship) {
            if ($scholarship->fixed_amount) {
                $totalDiscount += $scholarship->fixed_amount;
            } elseif ($scholarship->percentage) {
                $totalDiscount += ($this->total_amount * $scholarship->percentage / 100);
            }
        }

        return $totalDiscount;
    }

    public function updateStatus()
    {
        if ($this->due_amount <= 0) {
            $this->status = 'payee';
        } elseif ($this->paid_amount > 0 && $this->paid_amount < ($this->total_amount - $this->discount_amount)) {
            $this->status = 'partiellement_payee';
        } elseif ($this->due_date < now() && $this->status != 'payee') {
            $this->status = 'en_retard';
        } elseif ($this->status == 'brouillon') {
            // Ne rien faire, garder le statut brouillon
        } else {
            $this->status = 'emise';
        }
    }

    public function issue()
    {
        $this->status = 'emise';
        $this->issue_date = now();
        return $this->save();
    }

    public function cancel()
    {
        $this->status = 'annulee';
        return $this->save();
    }

    public function addFeeType(FeeType $feeType, float $quantity = 1, float $discount = 0)
    {
        $baseAmount = $feeType->calculateAmountForPeriod($this->period, $quantity);
        $finalAmount = $baseAmount - $discount;

        $this->feeTypes()->attach($feeType->id, [
            'base_amount' => $baseAmount,
            'discount_amount' => $discount,
            'final_amount' => $finalAmount,
            'quantity' => $quantity,
        ]);

        $this->recalculateBalance();
    }

    public function removeFeeType(FeeType $feeType)
    {
        $this->feeTypes()->detach($feeType->id);
        $this->recalculateBalance();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
            
            if (empty($invoice->issue_date)) {
                $invoice->issue_date = now();
            }
        });
    }
}
