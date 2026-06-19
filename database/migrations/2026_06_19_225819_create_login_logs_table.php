<?php

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\AuthStatus;
use App\Domain\Auth\Enums\LoginProvider;
use App\Domain\Auth\Enums\LoginRiskLevel;
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
        Schema::create('login_logs', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('user_id')

                ->nullable()

                ->constrained('users')

                ->nullOnDelete();

            $table->string('email')->nullable()->index();

            $table->string('status')->default(AuthStatus::FAILED->value);

            $table->string('panel')->default(AuthPanel::CUSTOMER->value);

            $table->string('provider')->default(LoginProvider::PASSWORD->value);

            $table->string('risk_level')->default(LoginRiskLevel::LOW->value);

            $table->string('ip_address', 45)->nullable();

            $table->text('user_agent')->nullable();

            $table->string('device_name')->nullable();

            $table->string('failure_reason')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);

            $table->index(['email', 'status']);

            $table->index(['panel', 'risk_level']);

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
