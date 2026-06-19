<?php

use App\Domain\Auth\Enums\OtpPurpose;
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
        Schema::create('login_otps', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('user_id')

                ->nullable()

                ->constrained('users')

                ->nullOnDelete();

            $table->string('identifier')->index();

            $table->string('code');

            $table->string('purpose')->default(OtpPurpose::LOGIN->value);

            $table->unsignedTinyInteger('attempts')->default(0);

            $table->timestamp('expires_at')->index();

            $table->timestamp('verified_at')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['identifier', 'purpose']);

            $table->index(['user_id', 'purpose']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_otps');
    }
};
