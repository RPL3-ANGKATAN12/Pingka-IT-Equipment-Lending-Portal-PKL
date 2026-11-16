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
            ->whereIn('status', ['selesai', 'borrowed', 'completed'])
            ->whereHas('item')
            ->orderByRaw('returned_at IS NOT NULL')
            ->orderByDesc('id')
            ->get();

        $stats = [
            'not_returned' => Loans::whereIn('status', ['selesai', 'borrowed', 'completed'])
                ->whereNull('returned_at')
                ->whereHas('item')
                ->count(),

            'returned' => Loans::whereIn('status', ['selesai', 'borrowed', 'completed'])
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
            return back()->with('error', 'The return due date can only be set after loan preparation is complete.');
        }

        if ($loan->returned_at !== null) {
            return back()->with('error', 'The date cannot be changed because the item has already been returned.');
        }

        $loan->due_date = $validated['due_date'];
        $loan->save();

        return back()->with('success', 'The return due date was saved successfully.');
    }

    public function confirmPickup(Loans $loan)
    {
        DB::transaction(function () use ($loan) {
            $lockedLoan = Loans::whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLoan->status !== 'selesai') {
                throw ValidationException::withMessages([
                    'pickup' => 'This loan is not waiting for pickup.',
                ]);
            }

            if ($lockedLoan->returned_at !== null) {
                throw ValidationException::withMessages([
                    'pickup' => 'This item has already been returned.',
                ]);
            }

            $lockedLoan->status = 'borrowed';
            $lockedLoan->picked_up_at = now();
            $lockedLoan->save();
        });

        return back()->with('success', 'Item pickup confirmed successfully.');
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
                    'returned' => 'Item pickup has not been confirmed.',
                ]);
            }

            if ($lockedLoan->returned_at !== null) {
                throw ValidationException::withMessages([
                    'returned' => 'This item has already been confirmed as returned.',
                ]);
            }

            $item = Item::whereKey($lockedLoan->item_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Save the due date and return time.
            $lockedLoan->due_date = $validated['due_date'];
            $lockedLoan->returned_at = now();
            $lockedLoan->status = 'completed';
            $lockedLoan->save();

            // Release the returned serial number for serial-number-tracked items.
            if ($item->tracks_serial_numbers) {
                if (blank($lockedLoan->sn)) {
                    throw ValidationException::withMessages([
                        'returned' => 'No serial number has been assigned to this transaction.',
                    ]);
                }

                $serial = \App\Models\ItemSerialNumber::where('item_id', $item->id)
                    ->where('serial_number', $lockedLoan->sn)
                    ->lockForUpdate()
                    ->first();

                if (!$serial) {
                    throw ValidationException::withMessages([
                        'returned' => 'The serial number was not found in the item inventory.',
                    ]);
                }

                // Do not release the serial number while another transaction is using it.
                $usedByAnotherLoan = Loans::where('item_id', $item->id)
                    ->where('sn', $lockedLoan->sn)
                    ->where('id', '!=', $lockedLoan->id)
                    ->whereIn('status', ['process', 'selesai', 'borrowed'])
                    ->whereNull('returned_at')
                    ->exists();

                if ($usedByAnotherLoan) {
                    throw ValidationException::withMessages([
                        'returned' => 'This serial number is still assigned to another transaction that has not been returned.',
                    ]);
                }

                $serial->status = 'available';
                $serial->save();

            }

            $item->syncInventoryAvailability();
        });

        return back()->with(
            'success',
            'Return confirmed successfully. The device Serial Number is now available.'
        );
    }


}