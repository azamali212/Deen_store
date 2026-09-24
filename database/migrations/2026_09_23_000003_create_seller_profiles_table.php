<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Created ONLY when an application is approved — the live business.
        Schema::create('seller_profiles', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('seller_application_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            // Copied from the approved application; locked afterwards (C6).
            $table->string('store_name', 100)->unique();
            $table->string('business_name', 150);
            $table->string('business_type', 30);

            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->string('business_address')->nullable();

            $table->string('bank_account_title')->nullable();
            $table->string('bank_name')->nullable();

            // TEXT, not string: holds the ENCRYPTED value (D3), and the
            // ciphertext is far longer than the account number itself.
            $table->text('bank_account_number')->nullable();
            $table->string('bank_account_last4', 4)->nullable();

            $table->string('status', 20);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};
