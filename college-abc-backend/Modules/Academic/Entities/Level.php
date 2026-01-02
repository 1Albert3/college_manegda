<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Searchable;

class Level extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'cycle_id',
        'name',
        'code',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'cycle_id' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $searchable = ['name', 'code', 'description'];

    // Relations
    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function classRooms()
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function feeTypes()
    {
        return $this->hasMany(\Modules\Finance\Entities\FeeType::class);
    }

    public function students()
    {
        return $this->hasManyThrough(
            \Modules\Student\Entities\Student::class,
            ClassRoom::class,
            'level_id',
            'id',
            'id',
            'id'
        )->join('enrollments', function($join) {
            $join->on('students.id', '=', 'enrollments.student_id')
                 ->on('class_rooms.id', '=', 'enrollments.class_room_id');
        })->distinct();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCycle($query, int $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%");
        });
    }

    // Accessors
    public function getFormattedNameAttribute()
    {
        return ucfirst($this->name);
    }

    public function getFullNameAttribute()
    {
        return $this->cycle ? "{$this->cycle->name} - {$this->name}" : $this->name;
    }

    public function getClassRoomsCountAttribute()
    {
        return $this->classRooms()->count();
    }

    public function getStudentsCountAttribute()
    {
        return $this->students()->count();
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

    public function reorder(int $newOrder)
    {
        $this->order = $newOrder;
        return $this->save();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($level) {
            if (!$level->code) {
                $level->code = strtoupper(\Str::slug($level->name, '_'));
            }
            if (!isset($level->order)) {
                $maxOrder = static::where('cycle_id', $level->cycle_id)->max('order');
                $level->order = ($maxOrder ?? 0) + 1;
            }
        });
    }
}
