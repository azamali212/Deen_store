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
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();

            $table->string('user_id')->nullable();
            $table->ulid('session_id');

            $table->string('token', 255)->unique();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Indexes
            $table->index(['user_id']);
            $table->index(['session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
