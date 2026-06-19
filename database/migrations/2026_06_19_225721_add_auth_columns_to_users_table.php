<?php

use App\Domain\Auth\Enums\UserAccountStatus;
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

            $table->ulid('uuid')->unique()->after('id');

            $table->string('phone')->nullable()->after('email');

            $table->string('status')->default(UserAccountStatus::ACTIVE->value)->after('password');

            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');

            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            $table->index('uuid');

            $table->index('status');

            $table->index('phone');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
