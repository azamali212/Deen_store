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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_manager_id')->unique()->nullable();
            $table->unsignedBigInteger('store_manager_id')->unique()->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->foreignUlid('user_id')->nullable();
            $table->foreignId('shipping_zone_id')->nullable();
            $table->string('order_number', 50)->unique();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('shipping_amount', 10, 2)->default(0.00);
            $table->decimal('grand_total', 10, 2);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->enum('order_status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'escalated'])->default('pending');
            $table->string('tracking_number')->nullable();
            $table->text('shipping_address');
            $table->text('billing_address');
            $table->timestamp('delayed_at')->nullable();
            $table->boolean('is_fraudulent')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->string('escalation_status')->nullable();
            $table->timestamp('order_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
