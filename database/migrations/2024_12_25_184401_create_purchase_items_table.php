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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id'); // References the purchases table
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id');  // References the products table
            $table->unsignedBigInteger('unit_id');     // References the units table
            $table->integer('quantity');               // Number of units purchased
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 8, 2)->nullable(); // Discount applied to this item (if any)
            $table->decimal('subtotal', 10, 2);        // Subtotal (quantity * price)
            $table->decimal('price_per_piece', 10, 2);  // Unit price at purchase
            $table->date('expiration_date')->nullable(); // Expiration date for the batch
            $table->string('batch_number')->nullable(); // Batch number (optional)
            $table->timestamps();
            $table->softDeletes();
        
            // Foreign key constraints
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('variants')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
