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
        Schema::create('login__audits', function (Blueprint $table) {
            $table->id();

            $table->string('user_id', 26)->nullable()->index();
            $table->string('email')->nullable();
        
            $table->string('event'); 
            // login_success | login_failed | otp_sent | otp_failed | otp_verified | session_revoked | logout
        
            $table->string('session_id', 26)->nullable()->index();
        
            $table->string('ip')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
        
            $table->text('meta')->nullable(); // JSON for extra data
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login__audits');
    }
};
