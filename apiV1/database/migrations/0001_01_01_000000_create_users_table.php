<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Social Media add in my porject 
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {

            //Auth
            $table->ulid('id')->primary()->defaultUlid();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('location')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('confirm_password')->nullable();

            //Pyment Fields 
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('default_payment_method')->nullable();

            //Other
            $table->softDeletes();
            $table->string('email_verification_token', 60)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('inactive');
            $table->timestamp('last_login_at')->nullable();
            $table->string('account_type')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
