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
        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');

            // Use string columns for ULIDs (26 chars)
            $table->string('subject_type')->nullable();
            $table->string('subject_id', 26)->nullable();
            $table->string('event')->nullable(); // Added
            $table->string('causer_type')->nullable();
            $table->string('causer_id', 26)->nullable();

            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable(); // Added
            $table->timestamps();

            // Indexes
            $table->index('log_name');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
