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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('supplier_category_id')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->boolean('blacklisted')->default(false);
            $table->text('blacklist_reason')->nullable();
            $table->enum('contract_status', ['active', 'terminated', 'pending'])->default('active');
            $table->decimal('performance_rating', 3, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
