<?php

use Illuminate\Support\Facades\Route;
use App\Models\Item;

Route::get('/items', function () {
    return response()->json([
        'data' => Item::where('status', 'tersedia')
            ->where('available', '>', 0)
            ->select(['id', 'name', 'available', 'status'])
            ->orderBy('name')
            ->get()
    ]);
});