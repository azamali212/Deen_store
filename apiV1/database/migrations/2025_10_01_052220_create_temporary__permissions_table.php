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
        Schema::create('temporary__permissions', function (Blueprint $table) {
            $table->id();
            // Use ulid to match users table
            $table->ulid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Permission ID (bigint from spatie permissions table)
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');

            // Assigned by (also ulid to match users table)
            $table->ulid('assigned_by');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');

            // Use datetime instead of timestamp to avoid MySQL issues
            $table->dateTime('assigned_at')->useCurrent();
            $table->dateTime('expires_at');
            $table->boolean('is_active')->default(true);
            $table->text('reason')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'permission_id']);
            $table->index(['expires_at', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary__permissions');
    }
};
