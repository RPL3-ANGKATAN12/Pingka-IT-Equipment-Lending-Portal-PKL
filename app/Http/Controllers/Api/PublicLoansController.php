<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loans;
use Illuminate\Http\Request;

class PublicLoansController extends Controller
{
    public function index()
    {
        $loansList = Loans::with('employee')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($loans) {
                return [
                    'id' => $loans->id,
                    'judul' => $loans->judul,
                    'deskripsi' => $loans->deskripsi,
                    'status' => $loans->status,
                    'respon' => $loans->respon,
                    'respon_at' => $loans->respon_at,
                    'created_at' => $loans->created_at,
                    'employee' => $loans->employee ? [
                        'nama' => $loans->employee->nama,
                        'hostname' => $loans->employee->hostname,
                        'departement' => $loans->employee->departement,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $loansList
        ]);
    }
}