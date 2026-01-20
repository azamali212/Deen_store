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
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
            $table->index('created_by');
            $table->unique(['name', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_role_requests');
    }
};