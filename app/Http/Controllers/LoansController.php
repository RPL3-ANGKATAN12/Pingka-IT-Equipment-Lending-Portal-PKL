<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loans;
use App\Models\Request as LoanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoansController extends Controller
{
    public function index()
    {
        $loans = Loans::with('item')
            ->latest()
            ->get()
            ->each(function ($loan) {
                $loan->hostname = null;
                $loan->type = null;
                $loan->sn = null;
                $loan->others = null;
            });

        $requests = LoanRequest::with('item')
            ->whereIn('status', ['pending', 'rejected'])
            ->latest()
            ->get();

        $requests->each(function ($request) {
            $request->others = null;
            $request->hostname = null;
            $request->type = null;
            $request->sn = null;

            if ($request->status === 'rejected') {
                $request->status = 'ditolak';
            }
        });

        $loans = $loans
            ->concat($requests)
            ->sortByDesc('created_at')
            ->values();

        $items = Item::orderBy('name')
            ->get();

        return view('guest.loans', compact('loans', 'items'));
    }

    public function create()
    {
        return redirect()->route('guest.home', ['request' => '1']);
    }

    public function track(string $token)
    {
        $loan = Loans::with('item')
            ->where('public_token', $token)
            ->firstOrFail();

        return view('guest.track', compact('loan'));
    }

    public function items()
    {
        $items = Item::orderBy('name')->get();

        return view('guest.items', compact('items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'departement' => 'required|string|max:100',
            'item_id' => 'required|exists:items,id',
            'start_date' => 'required|date',
            'others' => 'nullable|string',
        ]);

        $item = Item::findOrFail($validated['item_id']);

        if ($item->status === 'maintenance' || $item->available <= 0) {
            return back()
                ->withInput()
                ->with('error', 'This item is currently unavailable.');
        }

        LoanRequest::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'departement' => $validated['departement'],
            'item_id' => $item->id,
            'start_date' => $validated['start_date'],
            'others' => $validated['others'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('guest.home')
            ->with(
                'success',
                'Loan request sent successfully and is awaiting admin approval.'
            );
    }
}