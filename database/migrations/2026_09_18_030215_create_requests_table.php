<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('departement');

            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnDelete();

            $table->date('start_date');

            $table->text('others')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->timestamps();
        }); 
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};