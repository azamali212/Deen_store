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
        Schema::create('order_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable();
            $table->foreignUlid('user_id')->nullable(); // Who canceled the order
            $table->text('reason');
            $table->enum('cancellation_status', ['requested', 'approved', 'denied'])->default('requested');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order__cancellations');
    }
};
