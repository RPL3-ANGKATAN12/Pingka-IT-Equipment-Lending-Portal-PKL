<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('item_id')
                ->nullable()
                ->change();

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
        });

        // Pastikan tidak ada item_id NULL sebelum rollback.
        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('item_id')
                ->nullable(false)
                ->change();

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->restrictOnDelete();
        });
    }
};
