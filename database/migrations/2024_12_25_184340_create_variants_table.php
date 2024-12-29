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
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->string('code');
            $table->decimal('costing', 10, 2);
            $table->decimal('price', 10, 2);
            $table->unsignedBigInteger('unit_id')->nullable(); 
            $table->integer('stock')->default(0);
            $table->integer('alert_quantity')->default(0);
            $table->decimal('previous_price', 10, 2)->nullable();
            $table->decimal('conversion_factor', 10 , 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
