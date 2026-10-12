<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('items', 'status')) {
            Schema::table('items', function (Blueprint $table) {
                $table->enum('status', [
                    'tersedia',
                    'dipinjam',
                    'maintenance',
                ])->default('tersedia')->after('id');
            });
        }   
    }

    public function down(): void
    {
        if (Schema::hasColumn('items', 'status')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
