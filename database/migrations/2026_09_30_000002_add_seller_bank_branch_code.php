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
            // P11-1 — the UK's sort code, the US routing number. Countries
            // that use an IBAN leave this null.
            //
            // C57 — TEXT because the `encrypted` cast stores ciphertext,
            // which is much longer than six digits. A sort code alone is
            // not much of a secret; a sort code TOGETHER with an account
            // number is exactly what moves money, and they live in the
            // same row, so they are protected the same way.
            $table->text('bank_branch_code')->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropColumn('bank_branch_code');
        });
    }
};
