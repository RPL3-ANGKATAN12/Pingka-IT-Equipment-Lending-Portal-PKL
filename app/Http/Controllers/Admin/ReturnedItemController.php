<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Loans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnedItemController extends Controller
{
    public function index()
    {
        $loans = Loans::with('item')
            ->where('status', 'selesai')
            ->whereHas('item')
            ->orderByRaw('returned_at IS NOT NULL')
            ->orderByDesc('id')
            ->get();

        return view('admin.returned-items', compact('loans'));
    }

    public function updateDueDate(Request $request, Loans $loan)
    {
        $validated = $request->validate([
            'due_date' => ['required', 'date'],
        ]);

        if ($loan->status !== 'selesai') {
            return back()->with('error', 'Hanya peminjaman berstatus selesai yang dapat diatur tanggal pengembaliannya.');
        }

        if ($loan->returned_at !== null) {
            return back()->with('error', 'Tanggal tidak dapat diubah karena barang sudah dikembalikan.');
        }

        $loan->due_date = $validated['due_date'];
        $loan->save();

        return back()->with('success', 'Tanggal batas pengembalian berhasil disimpan.');
    }

    public function markReturned(Request $request, Loans $loan)
    {
        $request->validate([
            'returned' => ['accepted'],
        ]);

        DB::transaction(function () use ($loan) {
            $lockedLoan = Loans::whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLoan->status !== 'selesai') {
                throw ValidationException::withMessages([
                    'returned' => 'Status peminjaman belum selesai.',
                ]);
            }

            if ($lockedLoan->returned_at !== null) {
                throw ValidationException::withMessages([
                    'returned' => 'Barang ini sudah dikonfirmasi kembali.',
                ]);
            }

            $item = Item::whereKey($lockedLoan->item_id)
                ->lockForUpdate()
                ->firstOrFail();

            $item->available = (int) $item->available + 1;

            if ($item->status !== 'maintenance') {
                $item->status = 'tersedia';
            }

            $item->save();

            $lockedLoan->returned_at = now();
            $lockedLoan->save();
        });

        return back()->with('success', 'The return has been successfully confirmed. The item stock has been updated.');
    }
}