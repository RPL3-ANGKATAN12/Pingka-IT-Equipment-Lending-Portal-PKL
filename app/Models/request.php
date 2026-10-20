<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Item;

class Request extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'name',
        'email',
        'departement',
        'item_id',
        'start_date',
        'others',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}