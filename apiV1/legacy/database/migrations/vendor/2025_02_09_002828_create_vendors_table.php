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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id(); // Vendor unique ID
            $table->string('user_id')->unique(); 
            $table->string('contact_email')->unique();
            $table->string('contact_phone');
            $table->text('address');
            $table->text('business_description');
            $table->unsignedBigInteger('store_manager_id');
            $table->string('logo')->nullable(); // Vendor logo
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
