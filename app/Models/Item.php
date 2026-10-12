<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $table = 'items';

    protected $fillable = [
        'name',
        'available',
        'hostname',
        'type',
        'sn',
        'status',
        'others',
    ];

    protected $casts = [
        'available' => 'integer',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loans::class, 'item_id');
    }
}
