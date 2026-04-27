<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_recipients', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('email_id')
                ->constrained('emails')
                ->cascadeOnDelete();

            $table->string('user_id', 26);

            $table->string('recipient_type', 10); // to, cc, bcc

            $table->timestamps();

            $table->index('email_id');
            $table->index('user_id');
            $table->index('recipient_type');

            $table->unique(['email_id', 'user_id', 'recipient_type'], 'email_recipient_unique');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_recipients');
    }
};