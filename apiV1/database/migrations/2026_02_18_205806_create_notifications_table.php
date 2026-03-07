<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('central_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_type', 120)->default('user');
            $table->string('type', 120);
            $table->json('payload')->nullable();
            $table->json('channels')->nullable();
            $table->string('locale', 10)->default('en');
            $table->string('status', 30)->default('pending');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id']);
            $table->index(['type', 'status']);
            $table->unique(
                ['recipient_id', 'recipient_type', 'type', 'idempotency_key'],
                'notifications_idempotency_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_notifications');
    }
};