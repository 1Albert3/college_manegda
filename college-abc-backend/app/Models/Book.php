<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'title',
        'author',
        'isbn',
        'category',
        'publisher',
        'published_year',
        'total_copies',
        'available_copies',
        'cover_image',
        'location',
        'status',
    ];

    /**
     * Historique des emprunts
     */
    public function loans(): HasMany
    {
        return $this->hasMany(BookLoan::class);
    }
}
