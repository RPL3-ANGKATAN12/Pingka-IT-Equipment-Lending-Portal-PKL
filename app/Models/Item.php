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
        'requires_hostname',
        'tracks_serial_numbers',
        'target_stock',
    ];

    protected $casts = [
        'available' => 'integer',
        'requires_hostname' => 'boolean',
        'tracks_serial_numbers' => 'boolean',
    ];

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(ItemSerialNumber::class)
            ->orderBy('serial_number');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loans::class, 'item_id');
    }
}