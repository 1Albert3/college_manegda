<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookLoan extends Model
{
    protected $fillable = [
        'book_id',
        'user_id',
        'loan_date',
        'due_date',
        'return_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
    ];

    /**
     * Le livre emprunté
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * L'utilisateur (élève/prof) qui a emprunté
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
