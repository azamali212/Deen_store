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
        Schema::create('product_recommendations', function (Blueprint $table) {
            $table->id(); // auto-increment primary key
            $table->string('product_id'); // foreign key for the product
            $table->json('recommended_product_ids'); // stores recommended products in JSON format
            $table->timestamps(); // created_at, updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_recommendations');
    }
};
