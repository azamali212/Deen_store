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
        Schema::create('trusted_devices', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('user_id')

                ->constrained('users')

                ->cascadeOnDelete();

            $table->string('fingerprint')->index();

            $table->string('device_name')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->text('user_agent')->nullable();

            $table->timestamp('trusted_until')->nullable();

            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'fingerprint']);
            

            $table->index('trusted_until');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
