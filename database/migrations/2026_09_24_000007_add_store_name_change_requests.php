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
            // P9-4 — no new table for one field. Deliberately NOT unique:
            // two sellers may both ASK for the same name, and the first one
            // approved wins. Uniqueness is enforced on store_name, which is
            // the column that is actually live (C44/C45).
            $table->string('pending_store_name', 100)->nullable()->after('store_name');
            $table->timestamp('store_name_requested_at')->nullable()->after('pending_store_name');

            $table->index('store_name_requested_at', 'seller_profiles_name_req_idx');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropIndex('seller_profiles_name_req_idx');
            $table->dropColumn(['pending_store_name', 'store_name_requested_at']);
        });
    }
};
