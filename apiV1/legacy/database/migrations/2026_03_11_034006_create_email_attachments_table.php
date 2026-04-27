<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_attachments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('email_id')
                ->constrained('emails')
                ->cascadeOnDelete();

            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('email_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};