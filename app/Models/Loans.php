<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loans extends Model
{
    protected $table = 'loans';

    protected $fillable = [
        'public_token',
        'name',
        'email',
        'departement',
        'item_id',
        'item',
        'hostname',
        'type',
        'sn',
        'start_date',
        'others',
        'status',
        'due_date',
        'returned_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'returned_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            Item::class,
            'item_id'
        );
    }
}