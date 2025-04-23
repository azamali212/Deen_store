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
        Schema::create('order_tracking', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');
            $table->string('status'); // e.g., "Packed", "Shipped", "Delivered"
            $table->text('location')->nullable(); // current location / warehouse / hub
            $table->decimal('latitude', 10, 7)->nullable(); // Geo-coordinates
            $table->decimal('longitude', 10, 7)->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('source')->nullable(); // e.g., "CourierAPI", "AdminPanel", "DriverApp"
            $table->text('notes')->nullable(); // internal notes (optional)

            $table->timestamp('tracked_at')->useCurrent(); // precise tracking timestamp
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order__trackings');
    }
};
