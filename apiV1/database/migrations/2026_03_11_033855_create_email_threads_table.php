<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_threads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('subject')->nullable();

            $table->string('created_by', 26);

            $table->timestamps();

            $table->index('created_by');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_threads');
    }
};