<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('hostname', 100)->nullable()->after('item_id');
            $table->string('type', 50)->nullable()->after('hostname');
            $table->string('sn', 100)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['hostname', 'type', 'sn']);
        });
    }
};