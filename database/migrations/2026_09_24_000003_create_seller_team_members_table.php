<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 — who works for which store, and in what role (BLUEPRINT s.12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_team_members', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('seller_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // owner | manager | staff  (maps to the Spatie roles)
            $table->string('role', 20);

            // invited | active | revoked. Revoked rows are KEPT so the
            // audit trail still shows who used to have access.
            $table->string('status', 20);

            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            // Re-inviting someone to the SAME store reuses their row
            // instead of adding a second one. "One person, one store"
            // (P7-1) is enforced in code, not here, so that a revoked
            // person can still join a different store later.
            $table->unique(['seller_profile_id', 'user_id'], 'seller_team_store_user_unique');

            $table->index(['seller_profile_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // C22 — every store that already exists gets its owner row, so
        // "which store is this user in" has one code path from day one.
        $now = now();

        DB::table('seller_profiles')->orderBy('id')->chunkById(100, function ($profiles) use ($now): void {
            DB::table('seller_team_members')->insert(
                $profiles->map(fn ($profile): array => [
                    'seller_profile_id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'role' => 'owner',
                    'status' => 'active',
                    'invited_by' => null,
                    'invited_at' => null,
                    'accepted_at' => $profile->created_at ?? $now,
                    'revoked_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_team_members');
    }
};
