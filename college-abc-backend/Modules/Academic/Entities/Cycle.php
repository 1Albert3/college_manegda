<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Searchable;

class Cycle extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $searchable = ['name', 'description'];

    // Relations
    public function levels()
    {
        return $this->hasMany(Level::class)->orderBy('order');
    }

    public function classRooms()
    {
        return $this->hasManyThrough(
            ClassRoom::class,
            Level::class,
            'cycle_id',
            'level_id'
        );
    }

    public function feeTypes()
    {
        return $this->hasMany(\Modules\Finance\Entities\FeeType::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    // Accessors
    public function getFormattedNameAttribute()
    {
        return ucfirst($this->name);
    }

    public function getLevelsCountAttribute()
    {
        return $this->levels()->count();
    }

    public function getClassRoomsCountAttribute()
    {
        return $this->classRooms()->count();
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

        static::creating(function ($cycle) {
            if (!$cycle->slug) {
                $cycle->slug = \Str::slug($cycle->name);
            }
            if (!isset($cycle->order)) {
                $cycle->order = static::max('order') + 1;
            }
        });
    }
}
