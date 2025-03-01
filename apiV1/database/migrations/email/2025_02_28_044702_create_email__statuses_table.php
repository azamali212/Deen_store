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
        Schema::create('email__statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_id');
            $table->enum('status', ['sent', 'received']); // Status of the email
            $table->enum('read_status', ['read', 'unread'])->default('unread');
            $table->enum('archive_status', ['archived', 'unarchived'])->default('unarchived');
            $table->timestamps();

            //$table->engine = 'InnoDB'; 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_statuses');
    }
};