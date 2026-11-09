<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE loans MODIFY status ENUM('pending', 'process', 'selesai', 'borrowed', 'completed', 'ditolak') NOT NULL DEFAULT 'pending'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('loans')
                ->where('status', 'completed')
                ->update(['status' => 'borrowed']);

            DB::statement(
                "ALTER TABLE loans MODIFY status ENUM('pending', 'process', 'selesai', 'borrowed', 'ditolak') NOT NULL DEFAULT 'pending'"
            );
        }
    }
};
