<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Searchable;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Modules\Core\Entities\User;

class Payment extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'receipt_number',
        'student_id',
        'fee_type_id',
        'academic_year_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'payer_name',
        'notes',
        'status',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'validated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['receipt_number', 'reference', 'payer_name'];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Scopes
    public function scopeValidated($query)
    {
        return $query->where('status', 'valide');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'en_attente');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'annule');
    }

    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByAcademicYear($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeByFeeType($query, int $feeTypeId)
    {
        return $query->where('fee_type_id', $feeTypeId);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('payment_date', now()->month)
                     ->whereYear('payment_date', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('payment_date', now()->year);
    }

    public function scopeOrderByRecent($query)
    {
        return $query->orderBy('payment_date', 'desc');
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    public function getPaymentMethodLabelAttribute()
    {
        return match($this->payment_method) {
            'especes' => 'Espèces',
            'cheque' => 'Chèque',
            'virement' => 'Virement bancaire',
            'mobile_money' => 'Mobile Money',
            'carte' => 'Carte bancaire',
            default => ucfirst($this->payment_method),
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'en_attente' => 'En attente',
            'valide' => 'Validé',
            'annule' => 'Annulé',
            default => ucfirst($this->status),
        };
    }

    public function getIsValidatedAttribute()
    {
        return $this->status === 'valide';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'en_attente';
    }

    public function getIsCancelledAttribute()
    {
        return $this->status === 'annule';
    }

    // Methods
    public function validate(User $user)
    {
        $this->status = 'valide';
        $this->validated_by = $user->id;
        $this->validated_at = now();
        return $this->save();
    }

    public function cancel()
    {
        $this->status = 'annule';
        return $this->save();
    }

    public static function generateReceiptNumber()
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return sprintf('REC%s%06d', $year, $count);
    }

    public function updateInvoiceBalance()
    {
        // Récupérer toutes les factures de l'élève pour l'année académique
        $invoices = Invoice::where('student_id', $this->student_id)
                          ->where('academic_year_id', $this->academic_year_id)
                          ->where('status', '!=', 'annulee')
                          ->get();

        foreach ($invoices as $invoice) {
            $invoice->recalculateBalance();
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->receipt_number)) {
                $payment->receipt_number = self::generateReceiptNumber();
            }
        });

        static::created(function ($payment) {
            $payment->updateInvoiceBalance();
        });

        static::updated(function ($payment) {
            if ($payment->wasChanged('amount') || $payment->wasChanged('status')) {
                $payment->updateInvoiceBalance();
            }
        });
    }
}
