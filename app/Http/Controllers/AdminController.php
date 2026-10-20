<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loans;
use Illuminate\Support\Str;
use App\Models\ItemSerialNumber;
use Illuminate\Support\Facades\DB;
use App\Models\Request as LoanRequest;
use Illuminate\Http\Request as HttpRequest;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_items' => Item::count(),
            'total_loans' => Loans::count(),
            'total_pending' => Loans::where('status', 'pending')->count(),
            'total_pending_request' => LoanRequest::where('status', 'pending')->count(),
            'total_process' => Loans::where('status', 'process')->count(),
            'total_selesai' => Loans::where('status', 'selesai')->count(),
            'total_ditolak' => Loans::where('status', 'ditolak')->count(),
            'total_borrowed' => Loans::where('status', 'process')->count(),
        ];

        $recentLoans = Loans::with('item')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentLoans'));
    }

    public function requests()
    {
        $pendingRequests = LoanRequest::with('item')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $historyRequests = LoanRequest::with('item')
            ->whereIn('status', ['approved', 'rejected'])
            ->latest()
            ->get();

        return view(
            'admin.requests',
            compact('pendingRequests', 'historyRequests')
        );
    }

    public function approveRequest(LoanRequest $request)
    {
        if ($request->status !== 'pending') {
            return back()->with(
                'error',
                'This request has already been processed.'
            );
        }

        $item = $request->item;

        if (!$item) {
            return back()->with(
                'error',
                'The requested item was not found.'
            );
        }

        if ($item->available <= 0) {
            return back()->with(
                'error',
                'The item is no longer available.'
            );
        }

        Loans::create([
            'public_token' => Str::random(40),
            'name' => $request->name,
            'email' => $request->email,
            'departement' => $request->departement,
            'item_id' => $request->item_id,
            'hostname' => null,
            'type' => null,
            'sn' => null,
            'start_date' => $request->start_date,
            'others' => $request->others,
            'status' => 'process',
        ]);

        $request->update([
            'status' => 'approved',
        ]);

        $newAvailable = max(0, $item->available - 1);

        $item->update([
            'available' => $newAvailable,
            'status' => $newAvailable > 0
                ? 'tersedia'
                : 'dipinjam',
        ]);

        return back()->with(
            'success',
            'Request approved successfully and added to Loan Data.'
        );
    }

    public function rejectRequest(LoanRequest $request)
    {
        if ($request->status !== 'pending') {
            return back()->with(
                'error',
                'This request has already been processed.'
            );
        }

        $request->update([
            'status' => 'rejected',
        ]);

        return back()->with(
            'success',
            'Request rejected successfully.'
        );
    }

    public function deleteRequest(LoanRequest $request)
    {
        $request->delete();

        return back()->with(
            'success',
            'Request data deleted successfully.'
        );
    }

    public function loans(HttpRequest $httpRequest)
    {
        $query = Loans::with('item')
            ->latest();

        if ($httpRequest->filled('status')) {
            $httpRequest->validate([
                'status' => [
                    'in:pending,process,selesai,ditolak',
                ],
            ]);

            $query->where('status', $httpRequest->status);
        }

        $loans = $query->get();

        $stats = [
            'Pending' => Loans::where('status', 'pending')->count(),
            'Process' => Loans::where('status', 'process')->count(),
            'Done' => Loans::where('status', 'selesai')->count(),
            'Rejected' => Loans::where('status', 'ditolak')->count(),
        ];

        $items = Item::orderBy('name')->get();

        return view(
            'admin.loans',
            compact('loans', 'stats', 'items')
        );
    }

    /**
     * Save loan details and mark the loan as completed.
     * Hostname is required only when the item requires it.
     */
    
    public function updateLoan(HttpRequest $httpRequest, Loans $loan)
    {
        $validated = $httpRequest->validate([
            'hostname' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'sn' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($validated, $loan) {
                $loan = Loans::whereKey($loan->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $loan->load('item');
                $item = $loan->item;

                if (!$item) {
                    throw new \RuntimeException('Item not found.');
                }

                if ($loan->status === 'selesai') {
                    throw new \RuntimeException(
                        'Completed loans cannot be changed anymore.'
                    );
                }

                $usesSerialNumbers = (bool) $item->tracks_serial_numbers;
                $usesHostname = (bool) $item->requires_hostname;

                $sn = trim($validated['sn'] ?? '');
                $hostname = trim($validated['hostname'] ?? '');
                $type = trim($validated['type']);

                if ($usesSerialNumbers && $sn === '') {
                    throw new \RuntimeException(
                        'Serial Number wajib diisi untuk barang ini.'
                    );
                }

                if ($usesHostname && $hostname === '') {
                    throw new \RuntimeException(
                        'Hostname wajib diisi untuk barang ini.'
                    );
                }

                if ($usesSerialNumbers) {
                    $serial = ItemSerialNumber::where('item_id', $item->id)
                        ->where('serial_number', $sn)
                        ->lockForUpdate()
                        ->first();

                    if (!$serial) {
                        throw new \RuntimeException(
                            'Serial Number tidak terdaftar pada barang ini.'
                        );
                    }

                    // Jika SN sudah dipakai oleh peminjaman lain, tolak.
                    $serialUsedByAnotherLoan = Loans::where('item_id', $item->id)
                        ->where('sn', $sn)
                        ->where('id', '!=', $loan->id)
                        ->whereIn('status', ['process', 'selesai'])
                        ->exists();

                    if ($serialUsedByAnotherLoan) {
                        throw new \RuntimeException(
                            'Serial Number ini sudah dialokasikan ke peminjaman lain.'
                        );
                    }

                    // SN berstatus borrowed tidak boleh dipakai ulang,
                    // kecuali memang masih terikat pada transaksi ini.
                    if (
                        $serial->status !== 'available' &&
                        $loan->sn !== $sn
                    ) {
                        throw new \RuntimeException(
                            'Serial Number ini tidak tersedia.'
                        );
                    }

                    // Lepaskan SN lama milik transaksi ini jika diganti.
                    if (
                        filled($loan->sn) &&
                        $loan->sn !== $sn
                    ) {
                        ItemSerialNumber::where('item_id', $item->id)
                            ->where('serial_number', $loan->sn)
                            ->where('status', 'borrowed')
                            ->update(['status' => 'available']);
                    }

                    $serial->update(['status' => 'borrowed']);
                } else {
                    $sn = '';
                }

                $loan->hostname = $usesHostname && $hostname !== ''
                    ? $hostname
                    : null;

                $loan->type = $type;
                $loan->sn = $sn !== '' ? $sn : null;
                $loan->status = 'selesai';
                $loan->save();

                // Hitung ulang stok berdasarkan SN yang tersedia.
                if ($usesSerialNumbers) {
                    $item->available = ItemSerialNumber::where('item_id', $item->id)
                        ->where('status', 'available')
                        ->count();

                    if ($item->status !== 'maintenance') {
                        $item->status = $item->available > 0
                            ? 'tersedia'
                            : 'dipinjam';
                    }

                    $item->save();
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Device Info saved successfully and Serial Number allocated.'
        );
    }


    public function updateStatus(HttpRequest $httpRequest, Loans $loan)
    {
        $validated = $httpRequest->validate([
            'status' => 'required|in:pending,process,selesai,ditolak',
        ]);

        try {
            DB::transaction(function () use ($validated, $loan) {
                $loan = Loans::whereKey($loan->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $loan->load('item');

                $oldStatus = $loan->status;
                $newStatus = $validated['status'];
                $item = $loan->item;

                if ($oldStatus === 'selesai') {
                    throw new \RuntimeException(
                        'Completed loans cannot be changed anymore.'
                    );
                }

                if ($oldStatus === $newStatus) {
                    throw new \RuntimeException(
                        "Loan status is already {$newStatus}."
                    );
                }

                if (
                    $newStatus === 'selesai' &&
                    $item?->requires_hostname &&
                    blank($loan->hostname)
                ) {
                    throw new \RuntimeException(
                        'Hostname must be filled in before completing this loan.'
                    );
                }

                if (!$item) {
                    throw new \RuntimeException('Item not found.');
                }

                $usesSerialNumbers = (bool) $item->tracks_serial_numbers;

                if ($usesSerialNumbers && $newStatus === 'process') {
                    if (blank($loan->sn)) {
                        throw new \RuntimeException(
                            'Select a Serial Number before processing this loan.'
                        );
                    }

                    $serial = ItemSerialNumber::where('item_id', $item->id)
                        ->where('serial_number', $loan->sn)
                        ->lockForUpdate()
                        ->first();

                    if (!$serial || $serial->status !== 'available') {
                        throw new \RuntimeException(
                            'This Serial Number is no longer available. Select another SN.'
                        );
                    }

                    $serial->update(['status' => 'borrowed']);
                }

                if (
                    $usesSerialNumbers &&
                    $oldStatus === 'process' &&
                    $newStatus === 'ditolak' &&
                    filled($loan->sn)
                ) {
                    ItemSerialNumber::where('item_id', $item->id)
                        ->where('serial_number', $loan->sn)
                        ->where('status', 'borrowed')
                        ->update(['status' => 'available']);
                }

                if (!$usesSerialNumbers) {
                    if ($oldStatus === 'process' && $newStatus === 'ditolak') {
                        $item->available++;
                    }

                    if ($oldStatus !== 'process' && $newStatus === 'process') {
                        if ($item->available <= 0) {
                            throw new \RuntimeException(
                                'This item is currently unavailable.'
                            );
                        }

                        $item->available--;
                    }
                }

                // Sinkronkan jumlah stok dengan SN yang benar-benar tersedia.
                if ($usesSerialNumbers) {
                    $item->available = ItemSerialNumber::where('item_id', $item->id)
                        ->where('status', 'available')
                        ->count();
                }

                $item->status = $item->status === 'maintenance'
                    ? 'maintenance'
                    : ($item->available > 0 ? 'tersedia' : 'dipinjam');

                $item->save();

                $loan->update(['status' => $newStatus]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Loan status updated successfully.'
        );
    }
}