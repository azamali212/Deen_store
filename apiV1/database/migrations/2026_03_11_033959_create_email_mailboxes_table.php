<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_mailboxes', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('email_id')
                ->constrained('emails')
                ->cascadeOnDelete();

            $table->string('user_id', 26);

            $table->string('owner_type', 20); // sender, to, cc, bcc
            $table->string('folder', 20)->default('inbox'); // inbox, sent, draft, trash

            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_draft')->default(false);

            $table->timestamp('read_at')->nullable();
            $table->timestamp('starred_at')->nullable();
            $table->timestamp('trashed_at')->nullable();
            $table->timestamp('restored_at')->nullable();

            $table->timestamps();

            $table->index('email_id');
            $table->index('user_id');
            $table->index('folder');
            $table->index('owner_type');
            $table->index('is_read');
            $table->index('is_draft');

            $table->unique(['email_id', 'user_id'], 'email_mailbox_unique_per_user');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_mailboxes');
    }
};