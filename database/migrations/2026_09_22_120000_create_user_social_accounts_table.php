<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_social_accounts', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 'google' today (matches LoginProvider::GOOGLE); apple/facebook
            // will reuse this same table when they're added.
            $table->string('provider', 20);

            // The provider's own stable subject id (Google's "sub" claim) —
            // NOT the email, because a user's email can change but this
            // can't, and it's what we look the account up by on every
            // future login.
            $table->string('provider_user_id');

            $table->string('email')->nullable();

            $table->timestamps();

            // One provider account can only ever be linked to one user,
            // and can't be linked twice.
            $table->unique(['provider', 'provider_user_id']);

            // One user can only link a given provider once (one Google
            // account per user).
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_social_accounts');
    }
};
