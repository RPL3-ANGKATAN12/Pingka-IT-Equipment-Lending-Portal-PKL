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

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with('serialNumbers')
            ->orderBy('name')
            ->get();

        $activeLoanSerials = Loans::where('status', 'process')
            ->whereNotNull('sn')
            ->pluck('sn')
            ->map(fn ($sn) => mb_strtolower(trim($sn)))
            ->all();

        foreach ($items as $item) {
            if (!$item->tracks_serial_numbers) {
                continue;
            }

            $item->available = $item->serialNumbers->filter(
                fn ($serial) =>
                    $serial->status === 'available'
                    && !in_array(
                        mb_strtolower(trim($serial->serial_number)),
                        $activeLoanSerials,
                        true
                    )
            )->count();
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
        $targetStock = (int) $validated['available'];
        $serialNumbers = collect(preg_split('/\R/u', $validated['serial_numbers'] ?? '') ?: [])
            ->map(fn (string $serialNumber) => trim($serialNumber))
            ->filter(fn (string $serialNumber) => $serialNumber !== '')
            ->values();

        if (!$trackingEnabled && $serialNumbers->isNotEmpty()) {
            throw ValidationException::withMessages([
                'serial_numbers' => 'Aktifkan pelacakan Serial Number untuk menambahkan nomor seri.',
            ]);
        }

        if ($serialNumbers->count() > $targetStock) {
            throw ValidationException::withMessages([
                'serial_numbers' => 'Jumlah Serial Number tidak boleh melebihi Target Stock.',
            ]);
        }

        foreach ($serialNumbers as $serialNumber) {
            if (mb_strlen($serialNumber) > 150) {
                throw ValidationException::withMessages([
                    'serial_numbers' => 'Setiap Serial Number maksimal 150 karakter.',
                ]);
            }
        }

        $normalizedSerialNumbers = $serialNumbers
            ->map(fn (string $serialNumber) => mb_strtolower($serialNumber));

        if ($normalizedSerialNumbers->unique()->count() !== $normalizedSerialNumbers->count()) {
            throw ValidationException::withMessages([
                'serial_numbers' => 'Serial Number yang dimasukkan tidak boleh duplikat.',
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
                $item->serialNumbers()->createMany(
                    $serialNumbers->map(fn (string $serialNumber) => [
                        'serial_number' => $serialNumber,
                        'status' => 'available',
                    ])->all()
                );
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

        $newSerialNumbers = collect(preg_split('/\R/u', $validated['serial_numbers'] ?? '') ?: [])
            ->map(fn (string $serialNumber) => trim($serialNumber))
            ->filter(fn (string $serialNumber) => $serialNumber !== '')
            ->values();

        foreach ($newSerialNumbers as $serialNumber) {
            if (mb_strlen($serialNumber) > 150) {
                throw ValidationException::withMessages([
                    'serial_numbers' => 'Setiap Serial Number maksimal 150 karakter.',
                ]);
            }
        }

        $normalizedSerialNumbers = $newSerialNumbers
            ->map(fn (string $serialNumber) => mb_strtolower($serialNumber));

        if ($normalizedSerialNumbers->unique()->count() !== $normalizedSerialNumbers->count()) {
            throw ValidationException::withMessages([
                'serial_numbers' => 'Serial Number yang dimasukkan tidak boleh duplikat.',
            ]);
        }

        return DB::transaction(function () use ($request, $item, $validated, $newSerialNumbers, $normalizedSerialNumbers) {
            $item = Item::whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            $trackingEnabled = $request->boolean('tracks_serial_numbers');

            if (!$trackingEnabled && $newSerialNumbers->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'serial_numbers' => 'Aktifkan pelacakan Serial Number untuk menambahkan nomor seri.',
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
                ->whereIn('status', ['process', 'selesai'])
                ->whereNull('returned_at');

            if (
                $trackingEnabled &&
                $normalizedSerialNumbers->intersect($registeredSerialsNormalized)->isNotEmpty()
            ) {
                throw ValidationException::withMessages([
                    'serial_numbers' => 'Serial Number yang sudah terdaftar tidak perlu dimasukkan ulang.',
                ]);
            }

            if (
                $trackingEnabled &&
                $targetStock < $registeredSerialNumbers->count() + $newSerialNumbers->count()
            ) {
                return redirect()
                    ->route('admin.items.index')
                    ->with('error', 'Target Stock tidak boleh kurang dari jumlah SN yang sudah terdaftar dan ditambahkan.');
            }

            if (!$trackingEnabled && $targetStock < $activeLoans->count()) {
                return redirect()
                    ->route('admin.items.index')
                    ->with('error', 'Target Stock tidak boleh kurang dari jumlah barang yang sedang dipinjam.');
            }

            if ($trackingEnabled && $newSerialNumbers->isNotEmpty()) {
                $item->serialNumbers()->createMany(
                    $newSerialNumbers->map(fn (string $serialNumber) => [
                        'serial_number' => $serialNumber,
                        'status' => 'available',
                    ])->all()
                );
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
        ]);

        $serialNumber = trim($validated['serial_number']);

        if ($serialNumber === '') {
            return back()->withErrors([
                'serial_number' => 'Serial number is required.',
            ])->withInput();
        }

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

            $targetStock = (int) ($lockedItem->target_stock ?? 0);
            $registeredCount = $lockedItem->serialNumbers()->count();

            if ($registeredCount >= $targetStock) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'serial_number' => 'Target Stock sudah tercapai. Tambah Target Stock terlebih dahulu.',
                ]);
            }

            $lockedItem->serialNumbers()->create([
                'serial_number' => $serialNumber,
                'status' => 'available',
            ]);

            $activeLoanSerials = Loans::where('status', 'process')
                ->whereNotNull('sn')
                ->pluck('sn')
                ->map(fn ($sn) => mb_strtolower(trim($sn)))
                ->all();

            $availableCount = $lockedItem->serialNumbers()
                ->where('status', 'available')
                ->get()
                ->filter(fn ($serial) => !in_array(
                    mb_strtolower(trim($serial->serial_number)),
                    $activeLoanSerials,
                    true
                ))
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