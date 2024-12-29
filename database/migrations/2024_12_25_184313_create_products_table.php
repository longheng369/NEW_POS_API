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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['standard', 'service'])->default('standard');
            $table->string('code')->unique(); 
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->string('image')->nullable();
            $table->string('barcode_symbology')->default('code128');
            
            // Relations
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->default(1);
            $table->unsignedBigInteger('base_unit_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->decimal('conversion_factor', 10, 2)->nullable();
            
            $table->boolean('promotion')->default(false); 
            $table->decimal('discount')->nullable();
            $table->dateTime('start_date')->nullable(); 
            $table->dateTime('end_date')->nullable(); 
            $table->decimal('tax_rate', 8, 2)->nullable();
            $table->text('details')->nullable();
            $table->boolean('is_perishable')->default(true); 
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('base_unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            
            $table->index(['category_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
