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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no');
            $table->unsignedBigInteger('customer_id'); // Customer relation, nullable if walk-in
            $table->unsignedBigInteger('user_id'); // Cashier or salesperson who made the sale
            $table->decimal('tax_rate', 8, 2)->nullable(); // Tax applied to the sale
            $table->decimal('discount', 8, 2)->nullable(); // Discount applied to the total sale
            $table->string('status')->default('completed'); // Sale status: completed, pending, canceled
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        
            // Foreign key constraints
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); // Reference to the user table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
