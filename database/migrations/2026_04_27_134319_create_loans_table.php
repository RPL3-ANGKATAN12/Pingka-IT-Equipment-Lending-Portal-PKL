<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('public_token', 64)->unique();
            $table->string('name', 100);
            $table->string('departement', 100);
            $table->foreignId('item_id')
                ->constrained('items')
                ->restrictOnDelete();
            $table->date('start_date');
            $table->text('others')->nullable();
            $table->enum('status', [
                'pending',
                'process',
                'selesai',
                'ditolak'
            ])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};  