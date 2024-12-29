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
        Schema::create('daily_references', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique(); // Store the date of the reference count
            $table->integer('reference_count')->default(0); // Current count for the day
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_references');
    }
};
