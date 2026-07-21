<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('password');
            $table->timestamp('locked_at')->nullable()->after('failed_login_attempts');
            $table->timestamp('locked_until')->nullable()->after('locked_at');
            $table->string('lock_reason')->nullable()->after('locked_until');
            $table->timestamp('last_failed_login_at')->nullable()->after('lock_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'failed_login_attempts',
                'locked_at',
                'locked_until',
                'lock_reason',
                'last_failed_login_at',
            ]);
        });
    }
};