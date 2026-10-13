<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loans;
use Illuminate\Http\Request as HttpRequest;
use App\Models\Request as LoanRequest;
use Illuminate\Support\Str;

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

    public function requests(){
        $pendingRequests = LoanRequest::with('item')
            ->where(
                'status',
                'pending'
            )
            ->latest()
            ->get();

        $historyRequests = LoanRequest::with('item')
            ->whereIn('status',['approved','rejected',])
            ->latest()
            ->get();

        return view(
            'admin.requests', compact('pendingRequests','historyRequests')
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

    public function rejectRequest(LoanRequest $request){
        if ($request->status !== 'pending') {
            return back()->with(
                'error',
                'This request has already been processed.'
            );
        }

        $request->update(['status' => 'rejected',]);

        return back()->with(
            'success',
            'Request rejected successfully.'
        );
    }

    public function deleteRequest(LoanRequest $request){
        $request->delete();

        return back()->with(
            'success',
            'Request data deleted successfully.'
        );
    }

    public function loans(HttpRequest $httpRequest){
        $query = Loans::with('item')
            ->latest();

        if ($httpRequest->filled('status')) {
            $httpRequest->validate([
                'status' => [
                    'in:pending,process,selesai,ditolak',
                ],
            ]);

            $query->where(
                'status',
                $httpRequest->status
            );
        }

        $loans = $query->get();

        $stats = [
            'Pending' => Loans::where('status', 'pending')->count(),
            'Process' => Loans::where('status', 'process')->count(),
            'Done' => Loans::where('status', 'selesai')->count(),
            'Rejected' => Loans::where('status', 'ditolak')->count(),

        ];

        $items = Item::orderBy('name')->get();

        return view('admin.loans', compact('loans', 'stats', 'items' ));
    }

    
    public function updateLoan(HttpRequest $httpRequest, Loans $loan)
    {
        $validated = $httpRequest->validate([
            'hostname' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'sn' => ['nullable', 'string', 'max:255'],
        ]);

        // Simpan hostname, type, dan serial number
        $loan->hostname = $validated['hostname'] ?? null;
        $loan->type = $validated['type'];
        $loan->sn = $validated['sn'] ?? null;

        // Set status menjadi selesai setelah Edit disimpan
        $loan->status = 'selesai';

        $loan->save();

        return back()->with(
            'success',
            'Loan data updated and marked as completed successfully.'
        );
    }


    public function updateStatus(HttpRequest $httpRequest, Loans $loan)
    {
        $validated = $httpRequest->validate([
            'status' => [
                'required',
                'in:pending,process,selesai,ditolak',
            ],
        ]);

        $oldStatus = $loan->status;
        $newStatus = $validated['status'];

        if ($loan->status === 'selesai') {
            return back()->with(
                'error',
                'Completed loans cannot be changed anymore.'
            );
        }

        if ($oldStatus === $newStatus) {
            return back()->with(
                'success',
                "Loan status is already {$newStatus}."
            );
        }

        if ($loan->item) {

            if ($oldStatus === 'process' && in_array($newStatus, ['selesai', 'ditolak'])) {

                $newAvailable = $loan->item->available + 1;

                $loan->item->update([
                    'available' => $newAvailable,
                    'status' => 'tersedia',
                ]);
            }

            if ($oldStatus !== 'process' && $newStatus === 'process') {

                if ($loan->item->available <= 0) {
                    return back()->with(
                        'error',
                        'This item is currently unavailable.'
                    );
                }

                $newAvailable = $loan->item->available - 1;

                $loan->item->update([
                    'available' => $newAvailable,
                    'status' => $newAvailable > 0
                        ? 'tersedia'
                        : 'dipinjam',
                ]);
            }
        }

        $loan->update([
            'status' => $newStatus,
        ]);

        return back()->with(
            'success',
            "Loan status successfully changed from {$oldStatus} to {$newStatus}."
        );
    }}