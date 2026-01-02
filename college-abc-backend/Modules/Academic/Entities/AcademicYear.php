<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Searchable;
use Carbon\Carbon;

class AcademicYear extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'is_current',
        'description',
        'semesters'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'semesters' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $searchable = ['name', 'description'];

    // Relations
    public function enrollments()
    {
        return $this->hasMany(\Modules\Student\Entities\Enrollment::class);
    }

    public function semestersRelation()
    {
        return $this->hasMany(Semester::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'teacher_subject',
            'academic_year_id',
            'user_id'
        )->distinct();
    }

    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'teacher_subject',
            'academic_year_id',
            'subject_id'
        )->distinct();
    }

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeOngoing($query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    // Accessors & Methods
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
            return $this->status === 'completed' ? 100 : 0;
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        $elapsedDays = $this->start_date->diffInDays(now());

        return round(($elapsedDays / $totalDays) * 100, 1);
    }

    public function setAsCurrent()
    {
        // Unset current flag for all years
        static::where('is_current', true)->update(['is_current' => false]);

        // Set current year
        $this->update(['is_current' => true, 'status' => 'active']);

        return $this;
    }

    public function complete()
    {
        $this->update(['status' => 'completed', 'is_current' => false]);

        return $this;
    }

    public function generateName(): string
    {
        $startYear = $this->start_date->format('Y');
        $endYear = $this->end_date->format('Y');

        return $startYear . '-' . $endYear;
    }

    // Static methods
    public static function getCurrentYear()
    {
        return static::current()->first();
    }

    public static function createFromDates(Carbon $startDate, Carbon $endDate, array $attributes = [])
    {
        $attributes = array_merge([
            'name' => $startDate->format('Y') . '-' . $endDate->format('Y'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'planned',
        ], $attributes);

        return static::create($attributes);
    }
}
