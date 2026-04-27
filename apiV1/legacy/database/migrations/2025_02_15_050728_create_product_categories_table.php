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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->unsignedBigInteger('product_manager_id')->default(1)->nullable();
            $table->unsignedBigInteger('store_manager_id')->default(1)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index(); // Added index for better query performance
            $table->text('description')->nullable(); // Added for category description
            $table->string('image')->nullable(); // Added for category image
            $table->boolean('is_active')->default(true); // Added for category activation status
            $table->unsignedInteger('position')->default(0); // Added for category sorting order
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
