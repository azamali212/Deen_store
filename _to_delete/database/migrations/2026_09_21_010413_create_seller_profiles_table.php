<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table): void {

            $table->id();

            // One seller profile per user — a plain User can never end up
            // with two stores, and this is enforced at the DB level, not
            // just in application code.
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            // Public storefront name — unique platform-wide, the same way
            // a marketplace username/slug would be, since customers browse
            // by store name.
            $table->string('store_name');

            $table->string('business_name')
                ->nullable();

            $table->string('business_type')
                ->nullable();

            // Store goes live only after admin review — mirrors how a real
            // marketplace (Amazon/eBay) gates new sellers before they can
            // list products, not just after they sign up.
            $table->string('status', 20)
                ->default('pending');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            $table->text('rejection_reason')
                ->nullable();

            $table->timestamps();

            $table->unique('store_name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};
