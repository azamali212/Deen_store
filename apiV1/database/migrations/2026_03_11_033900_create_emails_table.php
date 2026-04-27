<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('thread_id')
                ->nullable()
                ->constrained('email_threads')
                ->nullOnDelete();

            $table->foreignId('parent_email_id')
                ->nullable()
                ->constrained('emails')
                ->nullOnDelete();

            $table->string('sender_id', 26);

            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->text('excerpt')->nullable();

            $table->string('priority', 20)->default('normal');
            $table->string('type', 30)->default('internal');
            $table->string('status', 30)->default('draft');

            $table->json('metadata')->nullable();

            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index('thread_id');
            $table->index('parent_email_id');
            $table->index('sender_id');
            $table->index('status');
            $table->index('priority');

            $table->foreign('sender_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};