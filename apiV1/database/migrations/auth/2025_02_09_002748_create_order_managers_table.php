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
        Schema::create('order_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id');
            $table->string('username')->unique(); 
            $table->string('phone_number')->nullable(); // Contact number
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active'); // Manager's status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_managers');
    }
};
