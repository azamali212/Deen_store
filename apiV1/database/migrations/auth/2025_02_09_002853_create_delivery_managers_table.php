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
        Schema::create('delivery_managers', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('phone_number')->nullable();
            $table->unsignedBigInteger('user_id')->unique(); // Foreign key to associate store manager with a user
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active'); // Store manager's status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_managers');
    }
};
