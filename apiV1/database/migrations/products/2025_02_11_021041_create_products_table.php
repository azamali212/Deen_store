<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Social Media add in my porject 
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedBigInteger('product_manager_id')->default(1)->nullable();
            $table->boolean('is_supplier_product')->default(false); // Identifies supplier products
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('store_manager_id')->default(1)->nullable();
            $table->unsignedBigInteger('vendor_id')->default(1)->nullable();
            $table->string('slug', 255)->unique();
            $table->text('description');
            $table->string('sku', 50);
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->integer('stock_quantity');
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('dimensions', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('limit')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('category_id')->default(1)->nullable();
            $table->unsignedBigInteger('brand_id')->default(1)->nullable();
            $table->timestamps();
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
