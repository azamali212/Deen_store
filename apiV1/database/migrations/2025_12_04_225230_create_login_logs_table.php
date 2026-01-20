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
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();

            $table->string('session_id', 26)->unique();
            $table->string('user_id', 26)->nullable()->index();
        
            $table->string('email')->nullable();
            $table->string('login_portal')->nullable();
            $table->string('guard')->nullable();
        
            $table->string('ip')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('timezone')->nullable();
        
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('device')->nullable();
        
            $table->boolean('success')->default(true);
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
