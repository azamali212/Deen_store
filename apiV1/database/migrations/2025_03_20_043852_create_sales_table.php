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
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('vendor_id');
            //$table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null')->index();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2); // Price per unit
            $table->decimal('total_price', 15, 2); // Final price after tax/discount
            $table->decimal('discount', 10, 2)->default(0.00); // Discount applied
            $table->decimal('tax', 10, 2)->default(0.00); // Tax applied
            $table->enum('status', ['pending', 'completed', 'refunded'])->default('pending');
            $table->timestamp('sale_date')->useCurrent();
            $table->timestamps();
            $table->softDeletes(); // Allows recovering deleted sales
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
