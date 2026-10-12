<?php

use Illuminate\Support\Facades\Route;
use App\Models\Item;

Route::get('/items', function () {
    return response()->json([
        'data' => Item::where('status', 'tersedia')
            ->orderBy('name')
            ->get()
    ]);
});