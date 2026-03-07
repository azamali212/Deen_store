<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('central_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_id')->constrained('central_notifications')->cascadeOnDelete();
            $table->string('channel', 30);
            $table->string('status', 30)->default('pending');
            $table->json('payload')->nullable();
            $table->string('external_id')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'channel']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_notification_deliveries');
    }
};