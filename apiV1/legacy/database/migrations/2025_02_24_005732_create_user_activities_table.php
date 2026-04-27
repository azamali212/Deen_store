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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('action', ['view', 'click', 'purchase', 'add_to_cart', 'wishlist', 'search']);
            $table->timestamp('action_time')->useCurrent();
            $table->string('device_type')->nullable();  // Track device type used for activity
            $table->string('ip_address')->nullable();   // Track IP address for location insights
            $table->text('additional_data')->nullable(); // Store additional metadata, e.g., session data, referrer

            $table->timestamps();

            $table->unique(['user_id', 'product_id', 'action', 'action_time']); // Ensure activity uniqueness
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};