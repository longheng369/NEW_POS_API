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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // The user (cashier/salesperson) handling the payment
            $table->unsignedBigInteger('sale_id')->nullable(); // Links to the sales table
            $table->unsignedBigInteger('purchase_id')->nullable(); 
            $table->decimal('amount', 10, 2); // Amount paid
            $table->string('payment_method'); // e.g., cash, card, etc.
            $table->dateTime('payment_date')->nullable(); // Date and time of the payment
            $table->string('status')->default('pending'); // Status of the payment (pending, completed)
            $table->timestamps();
            $table->softDeletes();
        
            // Foreign key constraints
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for performance
            $table->index('sale_id');
            $table->index('purchase_id');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
