<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('central_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('notification_type', 120)->default('*');
            $table->string('channel', 30);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'notification_type', 'channel'], 'notification_preferences_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_notification_preferences');
    }
};