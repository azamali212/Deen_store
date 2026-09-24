<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            // P8-5 — the seller's own exit. Separate from the suspension
            // columns on purpose: those record what an ADMIN did.
            $table->timestamp('closed_at')->nullable()->after('suspended_at');
            $table->text('closure_reason')->nullable()->after('closed_at');

            // P8-6 — the seller asks, an admin decides.
            $table->timestamp('reopen_requested_at')->nullable()->after('closure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropColumn(['closed_at', 'closure_reason', 'reopen_requested_at']);
        });
    }
};
