<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P12-1 / P12-3 — erasure is a request with a waiting period, not a button.
 *
 * erasure_requested_at — set when the user asks. Cleared if they cancel.
 * erased_at           — set once the anonymisation has actually run. It is
 *                       the flag that stops the nightly sweep touching the
 *                       same account twice, and the only honest answer to
 *                       "has this been done?".
 *
 * No new `status` value. The users.status column is a MySQL enum, and widening
 * an enum is a table rebuild on large tables and unreliable on SQLite. An
 * erased account is set to INACTIVE — which already cannot log in — and these
 * two timestamps carry the meaning (C62).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('erasure_requested_at')->nullable()->after('last_login_at');
            $table->timestamp('erased_at')->nullable()->after('erasure_requested_at');

            $table->index('erasure_requested_at', 'users_erasure_requested_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_erasure_requested_at_idx');
            $table->dropColumn(['erasure_requested_at', 'erased_at']);
        });
    }
};
