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
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->nullableMorphs('actor');
            $table->nullableMorphs('subject');
            $table->string('action', 100);
            $table->string('category', 50);
            $table->string('severity', 30);
            $table->string('status', 30)->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('panel', 50)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_name', 255)->nullable();
            $table->uuid('request_id')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['action', 'occurred_at']);
            $table->index(['category', 'occurred_at']);
            $table->index(['severity', 'occurred_at']);
            $table->index(['actor_type', 'actor_id', 'occurred_at']);
            $table->index(['subject_type', 'subject_id', 'occurred_at']);
            $table->index('request_id');
            $table->index('correlation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
