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
        Schema::create('order_shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('shipping_method_id');
            $table->unsignedBigInteger('delivery_manager_id')->nullable();
            $table->unsignedBigInteger('delivery_address_id');
            $table->string('tracking_number', 100)->unique();
            $table->string('carrier_name', 255);
            $table->enum('status', ['pending', 'shipped', 'in_transit', 'delivered', 'failed', 'returned']);
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('estimated_delivery')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shipments');
    }
};
