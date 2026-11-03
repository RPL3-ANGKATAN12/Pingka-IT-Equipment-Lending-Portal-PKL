<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dateTime('picked_up_at')->nullable()->after('due_date');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE loans MODIFY status ENUM('pending', 'process', 'selesai', 'borrowed', 'ditolak') NOT NULL DEFAULT 'pending'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('loans')
                ->where('status', 'borrowed')
                ->update(['status' => 'selesai']);

            DB::statement(
                "ALTER TABLE loans MODIFY status ENUM('pending', 'process', 'selesai', 'ditolak') NOT NULL DEFAULT 'pending'"
            );
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('picked_up_at');
        });
    }
};
