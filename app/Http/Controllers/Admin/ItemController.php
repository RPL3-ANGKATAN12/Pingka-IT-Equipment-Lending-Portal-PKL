<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemSerialNumber;
use App\Models\Loans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with([
            'serialNumbers',
            'loans' => fn ($query) => $query
                ->whereIn('status', ['process', 'selesai', 'borrowed'])
                ->whereNull('returned_at')
                ->select(['id', 'item_id', 'sn', 'status', 'returned_at']),
        ])
            ->orderBy('name')
            ->get();

        foreach ($items as $item) {
            $item->syncInventoryAvailability();
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
            'serial_numbers' => ['nullable', 'string', 'max:50000'],
            'type' => ['nullable', 'string', 'max:100'],
            'sn' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                Rule::in(['tersedia', 'dipinjam', 'maintenance']),
            ],
            'others' => ['nullable', 'string'],
        ]);

        $trackingEnabled = $request->boolean('tracks_serial_numbers');
        $hostnamesRequired = $trackingEnabled &&
            $request->boolean('requires_hostname');
        $targetStock = (int) $validated['available'];
        $serialNumbers = $this->parseSerialNumbers(
            $validated['serial_numbers'] ?? '',
            $hostnamesRequired
        );

        if (!$trackingEnabled && $serialNumbers->isNotEmpty()) {
            throw ValidationException::withMessages([
                'serial_numbers' => 'Enable Serial Number tracking to add serial numbers.',
            ]);
        }

        if ($serialNumbers->count() > $targetStock) {
            throw ValidationException::withMessages([
                'serial_numbers' => 'The number of serial numbers cannot exceed the target stock.',
            ]);
        }

        $normalizedSerialNumbers = $serialNumbers
            ->map(fn (array $serial) => mb_strtolower($serial['serial_number']));

        if ($normalizedSerialNumbers->unique()->count() !== $normalizedSerialNumbers->count()) {
            throw ValidationException::withMessages([
                'serial_numbers' => 'Serial numbers must not be duplicated.',
            ]);
        }

        $validated['target_stock'] = $targetStock;
        $validated['available'] = $trackingEnabled
            ? $serialNumbers->count()
            : $targetStock;
        $validated['requires_hostname'] =
            $request->boolean('requires_hostname');
        $validated['tracks_serial_numbers'] = $trackingEnabled;
        $validated['status'] = $targetStock > 0
            ? 'tersedia'
            : ($validated['status'] ?? 'dipinjam');
        unset($validated['serial_numbers']);

        DB::transaction(function () use ($validated, $serialNumbers) {
            $item = Item::create($validated);

            if ($serialNumbers->isNotEmpty()) {
                $item->serialNumbers()->createMany($serialNumbers->all());
            }
        });

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item added successfully.');
    }

    public function edit(Item $item)
    {
        return redirect()->route('admin.items.index');
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'available' => ['required', 'integer', 'min:0'],
            'hostname' => ['nullable', 'string', 'max:100'],
            'requires_hostname' => ['sometimes', 'boolean'],
            'tracks_serial_numbers' => ['sometimes', 'boolean'],
            'serial_numbers' => ['nullable', 'string', 'max:50000'],
            'type' => ['nullable', 'string', 'max:100'],
            'sn' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                Rule::in(['tersedia', 'dipinjam', 'maintenance']),
            ],
            'others' => ['nullable', 'string'],
        ]);

        $trackingEnabled = $request->boolean('tracks_serial_numbers');
        $hostnamesRequired = $trackingEnabled &&
            $request->boolean('requires_hostname');
        $newSerialNumbers = $this->parseSerialNumbers(
            $validated['serial_numbers'] ?? '',
            $hostnamesRequired
        );

        $normalizedSerialNumbers = $newSerialNumbers
            ->map(fn (array $serial) => mb_strtolower($serial['serial_number']));

        if ($normalizedSerialNumbers->unique()->count() !== $normalizedSerialNumbers->count()) {
            throw ValidationException::withMessages([
                'serial_numbers' => 'Serial numbers must not be duplicated.',
            ]);
        }

        return DB::transaction(function () use ($request, $item, $validated, $newSerialNumbers, $normalizedSerialNumbers, $trackingEnabled) {
            $item = Item::whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$trackingEnabled && $newSerialNumbers->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'serial_numbers' => 'Enable Serial Number tracking to add serial numbers.',
                ]);
            }

            if (!$trackingEnabled && $item->serialNumbers()->exists()) {
                return redirect()
                    ->route('admin.items.index')
                    ->with(
                        'error',
                        'Serial Number tracking cannot be disabled while serial numbers are registered.'
                    );
            }

            $targetStock = (int) $validated['available'];
            $registeredSerialNumbers = $item->serialNumbers()->get();
            $registeredSerialsNormalized = $registeredSerialNumbers
                ->map(fn ($serial) => mb_strtolower(trim($serial->serial_number)));
            $activeLoans = $item->loans()
                ->whereIn('status', ['process', 'selesai', 'borrowed'])
                ->whereNull('returned_at');

            if (
                $trackingEnabled &&
                $normalizedSerialNumbers->intersect($registeredSerialsNormalized)->isNotEmpty()
            ) {
                throw ValidationException::withMessages([
                    'serial_numbers' => 'Already registered serial numbers do not need to be entered again.',
                ]);
            }

            if (
                $trackingEnabled &&
                $targetStock < $registeredSerialNumbers->count() + $newSerialNumbers->count()
            ) {
                return redirect()
                    ->route('admin.items.index')
                    ->with('error', 'Target stock cannot be less than the number of registered and newly added serial numbers.');
            }

            if (!$trackingEnabled && $targetStock < $activeLoans->count()) {
                return redirect()
                    ->route('admin.items.index')
                    ->with('error', 'Target stock cannot be less than the number of items currently on loan.');
            }

            if ($trackingEnabled && $newSerialNumbers->isNotEmpty()) {
                $item->serialNumbers()->createMany($newSerialNumbers->all());
            }

            $validated['target_stock'] = $targetStock;
            if ($trackingEnabled) {
                $activeSerialNumbers = (clone $activeLoans)
                    ->whereNotNull('sn')
                    ->pluck('sn');

                $validated['available'] = $item->serialNumbers()
                    ->where('status', 'available')
                    ->whereNotIn('serial_number', $activeSerialNumbers)
                    ->count();
            } else {
                $validated['available'] = $targetStock - $activeLoans->count();
            }

            $validated['requires_hostname'] =
                $request->boolean('requires_hostname');

            $validated['tracks_serial_numbers'] = $trackingEnabled;
            unset($validated['serial_numbers']);
            if (!isset($validated['status']) && $item->status !== 'maintenance') {
                $validated['status'] = $validated['available'] > 0
                    ? 'tersedia'
                    : 'dipinjam';
            }

            $item->update($validated);
            $item->syncInventoryAvailability();

            return redirect()
                ->route('admin.items.index')
                ->with('success', 'Item updated successfully.');
        });
    }

    public function storeSerialNumber(Request $request, Item $item)
    {   
        abort_unless($item->tracks_serial_numbers, 404);

        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:150'],
            'hostname' => [
                $item->requires_hostname ? 'required' : 'nullable',
                'string',
                'max:100',
            ],
        ]);

        $serialNumber = trim($validated['serial_number']);
        $hostname = trim($validated['hostname'] ?? '');

        if ($serialNumber === '') {
            return back()->withErrors([
                'serial_number' => 'Serial number is required.',
            ])->withInput();
        }

        $currentlyBorrowed = Loans::where('sn', $serialNumber)
            ->whereIn('status', ['process', 'selesai', 'borrowed'])
            ->whereNull('returned_at')
            ->exists();

        if ($currentlyBorrowed) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'serial_number' => 'This serial number is currently borrowed and cannot be assigned to another item.',
            ]);
        }

        DB::transaction(function () use ($item, $serialNumber, $hostname) {
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

            $targetStock = (int) ($lockedItem->target_stock ?? 0);
            $registeredCount = $lockedItem->serialNumbers()->count();

            if ($registeredCount >= $targetStock) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'serial_number' => 'The target stock limit has been reached. Increase the target stock first.',
                ]);
            }

            $lockedItem->serialNumbers()->create([
                'serial_number' => $serialNumber,
                'hostname' => $hostname !== '' ? $hostname : null,
                'status' => 'available',
            ]);

            $lockedItem->syncInventoryAvailability();
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

        $item->syncInventoryAvailability();

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Serial number deleted successfully.');
    }

    public function destroy(Item $item)
    {
        if ($item->loans()->whereNotIn('status', ['completed', 'ditolak'])->exists()) {
            return redirect()
                ->route('admin.items.index')
                ->with(
                    'error',
                    'This item cannot be deleted while it has active loans or requests.'
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

    private function parseSerialNumbers(
        string $input,
        bool $hostnamesRequired
    ): Collection {
        $serialNumbers = collect();

        foreach (preg_split('/\R/u', $input) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $hostname = null;
            if ($hostnamesRequired) {
                $parts = explode(' - ', $line, 2);
                $serialNumber = trim($parts[0]);
                $hostname = isset($parts[1]) ? trim($parts[1]) : '';

                if ($serialNumber === '' || $hostname === '') {
                    throw ValidationException::withMessages([
                        'serial_numbers' => 'Enter each line in the format Serial Number - Hostname.',
                    ]);
                }
            } else {
                $serialNumber = $line;
            }

            if (mb_strlen($serialNumber) > 150) {
                throw ValidationException::withMessages([
                    'serial_numbers' => 'Each serial number must be no more than 150 characters.',
                ]);
            }

            if ($hostname !== null && mb_strlen($hostname) > 100) {
                throw ValidationException::withMessages([
                    'serial_numbers' => 'Each hostname must be no more than 100 characters.',
                ]);
            }

            $serialNumbers->push([
                'serial_number' => $serialNumber,
                'hostname' => $hostname,
                'status' => 'available',
            ]);
        }

        return $serialNumbers;
    }
}