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
        Schema::create('product__bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('product_id')->nullable();
            $table->foreignId('bundle_product_id')->nullable();
            $table->integer('bundle_quantity')->default(1); // Quantity of the bundled product
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product__bundles');
    }
};
