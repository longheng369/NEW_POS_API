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
        Schema::create('reference_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // e.g., 'sale', 'purchase'
            $table->string('prefix'); // e.g., 'SAL', 'PUR'
            $table->integer('current_number')->default(0); // The incrementing number
            $table->date('date'); // To reset numbers daily
            $table->unique(['type', 'date']); // Ensure unique numbers per type and day
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_numbers');
    }
};
