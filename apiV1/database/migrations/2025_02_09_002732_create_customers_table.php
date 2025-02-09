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
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->unsignedBigInteger('user_id')->unique(); // Foreign key to the users table
            $table->string('username')->unique();
            $table->string('post_code')->nullable(); 
            $table->string('phone_number')->nullable(); // Nullable
            $table->text('address');
            $table->string('city');
            $table->string('country');
            $table->string('profile_picture')->nullable(); 
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('preferred_language')->nullable();
            $table->boolean('newsletter_subscription')->default(true); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
