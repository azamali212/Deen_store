<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // P10-1 — the country belongs to the APPLICATION: it decides which
        // documents are asked for, and the admin verifies them under it.
        Schema::table('seller_applications', function (Blueprint $table): void {
            $table->char('country', 2)->nullable()->after('business_type');
            $table->index('country');
        });

        Schema::table('seller_profiles', function (Blueprint $table): void {
            // Copied at approval, like store_name and business_name (C6).
            $table->char('country', 2)->nullable()->after('business_type');
        });

        // Existing rows predate the column and are all Pakistani.
        DB::table('seller_applications')->whereNull('country')->update(['country' => 'PK']);
        DB::table('seller_profiles')->whereNull('country')->update(['country' => 'PK']);

        // P10-5 — the same column now holds a passport's expiry for a
        // seller who has no CNIC, so the CNIC name was a lie waiting to
        // confuse somebody. renameColumn keeps the data and the index.
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->renameColumn('cnic_expires_at', 'identity_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->renameColumn('identity_expires_at', 'cnic_expires_at');
        });

        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropColumn('country');
        });

        Schema::table('seller_applications', function (Blueprint $table): void {
            $table->dropIndex(['country']);
            $table->dropColumn('country');
        });
    }
};
