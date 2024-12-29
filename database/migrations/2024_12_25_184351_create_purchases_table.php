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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id'); // Links to the suppliers table
            $table->unsignedBigInteger('user_id'); // The user (employee) who made the purchase
            $table->decimal('tax', 8, 2)->nullable(); // Tax applied to the purchase
            $table->decimal('discount', 8, 2)->nullable(); // Discount applied, if any
            $table->string('status')->default('pending'); // Status of the purchase (pending, completed)
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
