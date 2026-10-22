<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loans;

class PublicLoansController extends Controller
{
    public function index()
    {
        $loansList = Loans::with('item:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Loans $loan) {
                return [
                    'id' => $loan->id,
                    'item' => $loan->item?->name,
                    'status' => $loan->status,
                    'start_date' => $loan->start_date?->toDateString(),
                    'created_at' => $loan->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $loansList
        ]);
    }
}