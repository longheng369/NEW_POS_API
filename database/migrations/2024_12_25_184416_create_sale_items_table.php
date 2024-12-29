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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id');
            $table->unsignedBigInteger('unit_id');
            $table->integer('quantity'); // Quantity of the product sold
            $table->decimal('unit_price', 10, 2);
            $table->decimal('price_per_piece', 10, 2);
            $table->decimal('discount', 8, 2)->nullable(); // Discount applied to this item (if any)
            $table->decimal('subtotal', 10, 2); // Price * quantity for this product in this sale
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('variants')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
