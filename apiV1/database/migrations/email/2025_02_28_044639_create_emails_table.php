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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->string('sender_id');
            $table->string('receiver_id');
            $table->string('from_email')->nullable()->index(); // Sender email address
            $table->string('to_email')->nullable()->index();   // Receiver email address
            $table->string('subject');
            $table->text('body');
            $table->string('draft_status')->default('draft');
            $table->timestamp('trashed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
