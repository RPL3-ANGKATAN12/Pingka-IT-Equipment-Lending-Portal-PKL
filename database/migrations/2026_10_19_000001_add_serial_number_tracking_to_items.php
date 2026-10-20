<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('items', 'tracks_serial_numbers')) {
            Schema::table('items', function (Blueprint $table) {
                $table->boolean('tracks_serial_numbers')
                    ->default(false);
            });
        }

        if (!Schema::hasTable('item_serial_numbers')) {
            Schema::create('item_serial_numbers', function (Blueprint $table) {
                $table->id();

                $table->foreignId('item_id')
                    ->constrained('items')
                    ->cascadeOnDelete();

                $table->string('serial_number', 150);

                $table->enum('status', [
                    'available',
                    'borrowed',
                    'maintenance',
                ])->default('available');

                $table->timestamps();

                $table->unique([
                    'item_id',
                    'serial_number',
                ]);
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak menghapus tabel atau kolom otomatis,
        // agar rollback tidak menghilangkan data Serial Number.
    }
};