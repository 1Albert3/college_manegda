<?php

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Searchable;

class FeeType extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'frequency',
        'cycle_id',
        'level_id',
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['name', 'description'];

    // Relations
    public function cycle()
    {
        return $this->belongsTo(\Modules\Academic\Entities\Cycle::class);
    }

    public function level()
    {
        return $this->belongsTo(\Modules\Academic\Entities\Level::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_fee_types')
                    ->withPivot('base_amount', 'discount_amount', 'final_amount', 'quantity')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_mandatory', false);
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function scopeByCycle($query, int $cycleId)
    {
        return $query->where(function ($q) use ($cycleId) {
            $q->where('cycle_id', $cycleId)
              ->orWhereNull('cycle_id');
        });
    }

    public function scopeByLevel($query, int $levelId)
    {
        return $query->where(function ($q) use ($levelId) {
            $q->where('level_id', $levelId)
              ->orWhereNull('level_id');
        });
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    public function getIsUniversalAttribute()
    {
        return is_null($this->cycle_id) && is_null($this->level_id);
    }

    // Methods
    public function activate()
    {
        $this->is_active = true;
        return $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        return $this->save();
    }

    public function isApplicableToStudent($student)
    {
        if (!$this->is_active) {
            return false;
        }

        $enrollment = $student->currentEnrollment;
        
        if (!$enrollment) {
            return false;
        }

        // Si le frais est universel, il s'applique à tous
        if ($this->is_universal) {
            return true;
        }

        // Vérifier le cycle si spécifié
        if ($this->cycle_id && $enrollment->classRoom->cycle_id != $this->cycle_id) {
            return false;
        }

        // Vérifier le niveau si spécifié
        if ($this->level_id && $enrollment->classRoom->level_id != $this->level_id) {
            return false;
        }

        return true;
    }

    public function calculateAmountForPeriod(string $period, int $months = 1)
    {
        return match($this->frequency) {
            'mensuel' => $this->amount * $months,
            'trimestriel' => $this->amount,
            'annuel' => $this->amount,
            'unique' => $this->amount,
            default => $this->amount,
        };
    }
}
