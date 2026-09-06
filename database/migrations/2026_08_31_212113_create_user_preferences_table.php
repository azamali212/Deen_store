<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('language', 10)
                ->default('en');

            $table->string('currency', 10)
                ->default('USD');

            $table->string('timezone')
                ->default('UTC');

            $table->string('theme', 20)
                ->default('system');

            $table->boolean('email_notifications')
                ->default(true);

            $table->boolean('sms_notifications')
                ->default(false);

            $table->boolean('push_notifications')
                ->default(true);

            $table->boolean('marketing_notifications')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
