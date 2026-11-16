<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Item extends Model
{
    protected $table = 'items';

    protected $fillable = [
        'name',
        'available',
        'hostname',
        'type',
        'sn',
        'status',
        'others',
        'requires_hostname',
        'tracks_serial_numbers',
        'target_stock',
    ];

    protected $casts = [
        'available' => 'integer',
        'requires_hostname' => 'boolean',
        'tracks_serial_numbers' => 'boolean',
    ];

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(ItemSerialNumber::class)
            ->orderBy('serial_number');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loans::class, 'item_id');
    }

    public function syncInventoryAvailability(): void
    {
        $loans = $this->relationLoaded('loans')
            ? $this->getRelation('loans')
            : $this->loans()->get(['id', 'item_id', 'sn', 'status', 'returned_at']);

        $activeLoans = $loans->filter(
            fn ($loan) =>
                in_array($loan->status, ['process', 'selesai', 'borrowed'], true)
                && $loan->returned_at === null
        );
        $targetStock = (int) $this->target_stock;

        if ($this->tracks_serial_numbers) {
            /** @var Collection $serialNumbers */
            $serialNumbers = $this->relationLoaded('serialNumbers')
                ? $this->getRelation('serialNumbers')
                : $this->serialNumbers()->get();

            $registeredNumbers = $serialNumbers
                ->map(fn ($serial) => mb_strtolower(trim($serial->serial_number)))
                ->all();
            $activeLoanSerials = $activeLoans
                ->pluck('sn')
                ->filter(fn ($sn) => filled($sn))
                ->map(fn ($sn) => mb_strtolower(trim($sn)))
                ->unique()
                ->values()
                ->all();
            $unassignedLoans = $activeLoans->filter(
                fn ($loan) =>
                    blank($loan->sn)
                    || !in_array(mb_strtolower(trim($loan->sn)), $registeredNumbers, true)
            )->count();

            $inUse = $serialNumbers->filter(
                fn ($serial) =>
                    $serial->status === 'borrowed'
                    || in_array(
                        mb_strtolower(trim($serial->serial_number)),
                        $activeLoanSerials,
                        true
                    )
            )->count() + $unassignedLoans;

            $available = max(
                0,
                $serialNumbers->filter(
                    fn ($serial) =>
                        $serial->status === 'available'
                        && !in_array(
                            mb_strtolower(trim($serial->serial_number)),
                            $activeLoanSerials,
                            true
                        )
                )->count() - $unassignedLoans
            );

            if ($targetStock === 0 && $serialNumbers->isNotEmpty()) {
                $this->target_stock = $serialNumbers->count();
            }
        } else {
            $inUse = $activeLoans->count();
            if ($targetStock === 0) {
                $targetStock = (int) $this->available + $inUse;
                $this->target_stock = $targetStock;
            }
            $available = max(0, $targetStock - $inUse);
        }

        $this->available = $available;

        if ($this->status !== 'maintenance') {
            $this->status = $available > 0 ? 'tersedia' : 'dipinjam';
        }

        if ($this->isDirty(['available', 'status', 'target_stock'])) {
            $this->save();
        }

        $this->setAttribute('in_use_count', $inUse);
        $this->syncOriginalAttribute('in_use_count');
    }
}