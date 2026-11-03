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
            ->whereIn('status', ['selesai', 'borrowed'])
            ->whereHas('item')
            ->orderByRaw('returned_at IS NOT NULL')
            ->orderByDesc('id')
            ->get();

        $stats = [
            'not_returned' => Loans::whereIn('status', ['selesai', 'borrowed'])
                ->whereNull('returned_at')
                ->whereHas('item')
                ->count(),

            'returned' => Loans::whereIn('status', ['selesai', 'borrowed'])
                ->whereNotNull('returned_at')
                ->whereHas('item')
                ->count(),
        ];

        return view('admin.returned-items', compact('loans', 'stats'));
    }


    public function updateDueDate(Request $request, Loans $loan)
    {
        $validated = $request->validate([
            'due_date' => ['required', 'date'],
        ]);

        if (!in_array($loan->status, ['selesai', 'borrowed'], true)) {
            return back()->with('error', 'Tanggal pengembalian hanya dapat diatur setelah persiapan peminjaman selesai.');
        }

        if ($loan->returned_at !== null) {
            return back()->with('error', 'Tanggal tidak dapat diubah karena barang sudah dikembalikan.');
        }

        $loan->due_date = $validated['due_date'];
        $loan->save();

        return back()->with('success', 'Tanggal batas pengembalian berhasil disimpan.');
    }

    public function confirmPickup(Loans $loan)
    {
        DB::transaction(function () use ($loan) {
            $lockedLoan = Loans::whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLoan->status !== 'selesai') {
                throw ValidationException::withMessages([
                    'pickup' => 'Peminjaman ini tidak sedang menunggu pengambilan.',
                ]);
            }

            if ($lockedLoan->returned_at !== null) {
                throw ValidationException::withMessages([
                    'pickup' => 'Barang ini sudah dikembalikan.',
                ]);
            }

            $lockedLoan->status = 'borrowed';
            $lockedLoan->picked_up_at = now();
            $lockedLoan->save();
        });

        return back()->with('success', 'Pengambilan barang berhasil dikonfirmasi.');
    }

    public function markReturned(Request $request, Loans $loan)
    {
        $validated = $request->validate([
            'returned' => ['required', 'accepted'],
            'due_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($loan, $validated) {
            $lockedLoan = Loans::whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLoan->status !== 'borrowed') {
                throw ValidationException::withMessages([
                    'returned' => 'Pengambilan barang belum dikonfirmasi.',
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

            // Simpan tanggal jatuh tempo dan waktu pengembalian.
            $lockedLoan->due_date = $validated['due_date'];
            $lockedLoan->returned_at = now();
            $lockedLoan->save();

            // Jika item menggunakan Serial Number, bebaskan SN yang dikembalikan.
            if ($item->tracks_serial_numbers) {
                if (blank($lockedLoan->sn)) {
                    throw ValidationException::withMessages([
                        'returned' => 'Serial Number pada transaksi ini belum diisi.',
                    ]);
                }

                $serial = \App\Models\ItemSerialNumber::where('item_id', $item->id)
                    ->where('serial_number', $lockedLoan->sn)
                    ->lockForUpdate()
                    ->first();

                if (!$serial) {
                    throw ValidationException::withMessages([
                        'returned' => 'Serial Number tidak ditemukan pada inventaris barang.',
                    ]);
                }

                // Jangan membebaskan SN jika transaksi lain masih menggunakannya.
                $usedByAnotherLoan = Loans::where('item_id', $item->id)
                    ->where('sn', $lockedLoan->sn)
                    ->where('id', '!=', $lockedLoan->id)
                    ->whereIn('status', ['process', 'selesai', 'borrowed'])
                    ->whereNull('returned_at')
                    ->exists();

                if ($usedByAnotherLoan) {
                    throw ValidationException::withMessages([
                        'returned' => 'Serial Number ini masih terhubung dengan transaksi lain yang belum dikembalikan.',
                    ]);
                }

                $serial->status = 'available';
                $serial->save();

                // Stok mengikuti jumlah SN yang benar-benar tersedia.
                $item->available = \App\Models\ItemSerialNumber::where(
                    'item_id',
                    $item->id
                )
                    ->where('status', 'available')
                    ->count();
            } else {
                // Item tanpa SN: tambah stok satu kali.
                $item->available = (int) $item->available + 1;
            }

            if ($item->status !== 'maintenance') {
                $item->status = $item->available > 0
                    ? 'tersedia'
                    : 'dipinjam';
            }

            $item->save();
        });

        return back()->with(
            'success',
            'Return confirmed successfully. The device Serial Number is now available.'
        );
    }


}