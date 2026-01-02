<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Searchable;

class Semester extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'name',
        'type',
        'number',
        'start_date',
        'end_date',
        'is_current',
        'description',
    ];

    protected $casts = [
        'academic_year_id' => 'integer',
        'number' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $searchable = ['name', 'description'];

    // Relations
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grades()
    {
        return $this->hasMany(\Modules\Grade\Entities\Grade::class);
    }

    // Scopes
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeByAcademicYear($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOngoing($query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('number');
    }

    // Accessors
    public function getFormattedNameAttribute()
    {
        return ucfirst($this->name);
    }

    public function getIsOngoingAttribute()
    {
        $now = now();
        return $this->start_date <= $now && $this->end_date >= $now;
    }

    public function getDurationAttribute()
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    public function getProgressPercentageAttribute()
    {
        if (!$this->isOngoing) {
            return $this->end_date < now() ? 100 : 0;
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        $elapsedDays = $this->start_date->diffInDays(now());

        return round(($elapsedDays / $totalDays) * 100, 1);
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->end_date < now()) {
            return 0;
        }

        return now()->diffInDays($this->end_date);
    }

    // Methods
    public function setAsCurrent()
    {
        // Unset current flag for all semesters of this academic year
        static::where('academic_year_id', $this->academic_year_id)
              ->where('is_current', true)
              ->update(['is_current' => false]);

        // Set current semester
        $this->update(['is_current' => true]);

        return $this;
    }

    public function complete()
    {
        $this->update(['is_current' => false]);

        return $this;
    }

    // Static methods
    public static function getCurrentSemester()
    {
        return static::current()->first();
    }

    public static function getOngoingSemester()
    {
        return static::ongoing()->first();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($semester) {
            if (!isset($semester->name)) {
                $typeName = $semester->type === 'trimester' ? 'Trimestre' : 'Semestre';
                $semester->name = "{$typeName} {$semester->number}";
            }
        });
    }
}
