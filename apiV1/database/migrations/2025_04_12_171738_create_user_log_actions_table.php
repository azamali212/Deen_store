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
        Schema::create('user_log_actions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');

            $table->string('action'); // e.g. "update_shift"
            $table->string('event_type')->nullable(); // e.g. login, delete, update
            $table->json('details')->nullable(); // full payload if needed
            $table->string('status')->nullable(); // e.g., success, failed

            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->string('device_type')->nullable(); // e.g. desktop, mobile
            $table->string('device_model')->nullable(); // e.g. iPhone 13
            $table->string('platform')->nullable(); // e.g. macOS, Android
            $table->string('browser')->nullable();
            $table->string('location')->nullable(); // city/country
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->string('route_name')->nullable(); // optional: name of route
            $table->string('url')->nullable(); // full URL

            $table->foreignUlid('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_log_actions');
    }
};
