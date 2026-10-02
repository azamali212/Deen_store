<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P12-5 — the consent ledger.
 *
 * Append-only by design. A row is never deleted and never rewritten; it is
 * closed by stamping withdrawn_at (the user said no) or superseded_at (the
 * wording changed and they agreed to the new one). That is what makes the
 * table evidence: it answers "were we allowed to do this, on that date?"
 * rather than only "are we allowed right now?".
 *
 * The foreign key deliberately has NO cascade. A hard delete of a user would
 * take the evidence with it, so the database refuses it outright (C61).
 * Erasure anonymises the user row instead; these rows survive it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')->constrained();

            $table->string('type', 40);
            $table->string('version', 40);

            $table->timestamp('granted_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('superseded_at')->nullable();

            // Evidence of the circumstances, not tracking. Kept for the same
            // reason the timestamp is: a consent record that cannot say where
            // it came from is weak evidence.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'type'], 'user_consents_user_type_idx');
            $table->index(['type', 'version'], 'user_consents_type_version_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};
