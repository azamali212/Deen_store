<?php

use App\Domain\Auth\Enums\AuthPanel;
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
        Schema::create('active_sessions', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('user_id')

                ->constrained('users')

                ->cascadeOnDelete();

            $table->string('token_id')->nullable()->index();

            $table->string('panel')->default(AuthPanel::CUSTOMER->value);

            $table->string('ip_address', 45)->nullable();

            $table->text('user_agent')->nullable();

            $table->string('device_name')->nullable();

            $table->string('browser')->nullable();

            $table->string('operating_system')->nullable();

            $table->timestamp('last_activity_at')->nullable();

            $table->timestamp('terminated_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'panel']);

            $table->index('terminated_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_sessions');
    }
};
