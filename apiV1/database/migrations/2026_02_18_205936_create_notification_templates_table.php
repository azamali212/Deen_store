<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('central_notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 120);
            $table->string('channel', 30);
            $table->string('locale', 10)->default('en');
            $table->text('subject_template')->nullable();
            $table->longText('body_template');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['type', 'channel', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_notification_templates');
    }
};