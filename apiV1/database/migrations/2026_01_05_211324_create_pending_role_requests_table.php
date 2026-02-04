<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_role_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->json('permission_names')->nullable();
            $table->text('description')->nullable();
            
            // Use ulid() to match the users table's id type
            $table->ulid('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            
            $table->ulid('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users');
            
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            
            // NEW: Additional tracking fields
            $table->timestamp('notified_at')->nullable();
            $table->integer('notification_attempts')->default(0);
            $table->json('notification_logs')->nullable();
            $table->boolean('escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->json('metadata')->nullable(); // Store additional data
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
            $table->index('created_by');
            $table->index(['status', 'created_at']); // Composite index for faster queries
            $table->index('notified_at');
            $table->unique(['name', 'created_by', 'status']); // Modified unique constraint
        });

        // Create notifications table if not exists
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->json('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // Create notification_preferences table
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->ulid('user_id');
            $table->string('channel'); // email, push, sms, in_app, slack, etc.
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users');
            $table->unique(['user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('pending_role_requests');
    }
};