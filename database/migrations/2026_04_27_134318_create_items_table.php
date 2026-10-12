<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('hostname', 100)->nullable();
            $table->string('type', 100)->nullable();
            $table->string('sn', 100)->nullable();
            $table->enum('status', [
                'tersedia',
                'dipinjam',
                'maintenance'
            ])->default('tersedia');
            $table->text('others')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};