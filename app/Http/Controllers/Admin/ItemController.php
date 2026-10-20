<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemSerialNumber;
use App\Models\Loans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with('serialNumbers')
            ->orderBy('name')
            ->get();

        // For SN-tracked equipment, serial-number records are the source of truth.
        // Reconcile the cached available count so legacy/stale stock values don't
        // contradict the number of physical units recorded in the SN list.
        foreach ($items as $item) {
            if (!$item->tracks_serial_numbers) {
                continue;
            }

            $activeLoanSerials = Loans::where('status', 'process')
                ->whereNotNull('sn')
                ->pluck('sn')
                ->map(fn ($sn) => mb_strtolower(trim($sn)))
                ->all();

            $availableCount = $item->serialNumbers->filter(function ($serial) use ($activeLoanSerials) {
                return $serial->status === 'available'
                    && !in_array(mb_strtolower(trim($serial->serial_number)), $activeLoanSerials, true);
            })->count();

            $item->available = $availableCount;
            if ((int) $item->getRawOriginal('available') !== $availableCount) {
                $item->forceFill([
                    'available' => $availableCount,
                    'status' => $availableCount > 0 ? 'tersedia' : 'dipinjam',
                ])->save();
            }
        }

        return view('admin.item', compact('items'));
    }

    public function create()
    {
        return redirect()->route('admin.items.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'available' => ['required', 'integer', 'min:0'],
            'hostname' => ['nullable', 'string', 'max:100'],
            'requires_hostname' => ['sometimes', 'boolean'],
            'tracks_serial_numbers' => ['sometimes', 'boolean'],
            'type' => ['nullable', 'string', 'max:100'],
            'sn' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                Rule::in(['tersedia', 'dipinjam', 'maintenance']),
            ],
            'others' => ['nullable', 'string'],
        ]);

        $validated['requires_hostname'] =
            $request->boolean('requires_hostname');

        $validated['tracks_serial_numbers'] =
            $request->boolean('tracks_serial_numbers');

        $validated['status'] = $validated['status'] ?? 'tersedia';

        Item::create($validated);

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item added successfully.');
    }

    public function edit(Item $item)
    {
        return view('admin.item-edit', compact('item'));
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'available' => ['required', 'integer', 'min:0'],
            'hostname' => ['nullable', 'string', 'max:100'],
            'requires_hostname' => ['sometimes', 'boolean'],
            'tracks_serial_numbers' => ['sometimes', 'boolean'],
            'type' => ['nullable', 'string', 'max:100'],
            'sn' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                Rule::in(['tersedia', 'dipinjam', 'maintenance']),
            ],
            'others' => ['nullable', 'string'],
        ]);

        $trackingEnabled = $request->boolean('tracks_serial_numbers');

        if (
            !$trackingEnabled &&
            $item->serialNumbers()->exists()
        ) {
            return redirect()
                ->route('admin.items.index')
                ->with(
                    'error',
                    'Tracking Serial Number tidak dapat dinonaktifkan selama masih ada Serial Number terdaftar.'
                );
        }

        $validated['requires_hostname'] =
            $request->boolean('requires_hostname');

        $validated['tracks_serial_numbers'] = $trackingEnabled;

        $item->update($validated);

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item updated successfully.');
    }

    public function storeSerialNumber(Request $request, Item $item)
    {   
        abort_unless($item->tracks_serial_numbers, 404);

        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:150'],
        ]);

        $serialNumber = trim($validated['serial_number']);

        if ($serialNumber === '') {
            return back()->withErrors([
                'serial_number' => 'Serial number is required.',
            ])->withInput();
        }

        // Never register a serial number that is currently assigned to an active loan,
        // even when an administrator is adding it under a different item.
        $currentlyBorrowed = Loans::where('sn', $serialNumber)
            ->where('status', 'process')
            ->exists();

        if ($currentlyBorrowed) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'serial_number' => 'This serial number is currently borrowed and cannot be assigned to another item.',
            ]);
        }

        DB::transaction(function () use ($item, $serialNumber) {
            $lockedItem = Item::whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Each registered SN represents one physical unit. Do not cap SN
            // registration using items.available: that value can be stale and is
            // reconciled from SN statuses for tracked items.
            $exists = $lockedItem->serialNumbers()
                ->where('serial_number', $serialNumber)
                ->exists();

            if ($exists) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'serial_number' => 'This serial number is already registered.',
                ]);
            }

            $lockedItem->serialNumbers()->create([
                'serial_number' => $serialNumber,
                'status' => 'available',
            ]);

            $availableCount = $lockedItem->serialNumbers()
                ->where('status', 'available')
                ->count();

            $lockedItem->update([
                'available' => $availableCount,
                'status' => $availableCount > 0 ? 'tersedia' : 'dipinjam',
            ]);
        });

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Serial number added successfully.');
    }

    public function destroySerialNumber(
        Item $item,
        ItemSerialNumber $serialNumber
    ) {
        abort_unless(
            (int) $serialNumber->item_id === (int) $item->id,
            404
        );

        if ($serialNumber->status === 'borrowed') {
            return redirect()
                ->route('admin.items.index')
                ->with(
                    'error',
                    'A borrowed serial number cannot be deleted.'
                );
        }

        $item = $serialNumber->item;
        $serialNumber->delete();

        if ($item->tracks_serial_numbers) {
            $availableCount = $item->serialNumbers()
                ->where('status', 'available')
                ->count();

            $item->update([
                'available' => $availableCount,
                'status' => $availableCount > 0 ? 'tersedia' : 'dipinjam',
            ]);
        }

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Serial number deleted successfully.');
    }

    public function destroy(Item $item)
    {
        if ($item->loans()->exists()) {
            return redirect()
                ->route('admin.items.index')
                ->with(
                    'error',
                    'This item cannot be deleted because it has loan history.'
                );
        }

        if ($item->serialNumbers()->where('status', 'borrowed')->exists()) {
            return redirect()
                ->route('admin.items.index')
                ->with(
                    'error',
                    'This item has a serial number that is currently borrowed.'
                );
        }

        DB::transaction(function () use ($item) {
            $item->serialNumbers()->delete();
            $item->delete();
        });

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item deleted successfully.');
    }
}