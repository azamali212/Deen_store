<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_applications', function (Blueprint $table): void {

            $table->id();

            // UNIQUE: a user can only ever have ONE application (D2 — a
            // rejected one is edited and resubmitted, never replaced).
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('store_name', 100)->unique();
            $table->string('business_name', 150);
            $table->string('business_type', 30);

            // No DB default on purpose (C9) — the code always sets it, so a
            // forgotten status fails loudly instead of silently defaulting.
            $table->string('status', 20)->index();

            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_applications');
    }
};
