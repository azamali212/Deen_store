<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_moderation_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('status', 20)->default('pending');
            $table->string('severity', 20);

            // Which fields the AI flagged, and why. Shape:
            // {"bio": {"category": "hate_speech", "reason": "..."}, "avatar": {...}}
            $table->json('flagged_fields');

            // Short plain-English summary of the whole check, for the admin list view.
            $table->text('ai_summary');

            // Snapshot of the profile values AT THE TIME of the check, so the
            // admin can still see what was flagged even if the user edits again
            // before review.
            $table->json('snapshot');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_moderation_flags');
    }
};
