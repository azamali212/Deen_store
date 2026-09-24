<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 — admin control of live sellers + a way out of "bank mismatch".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {

            // P6-1 — store suspension (the ACCOUNT stays usable).
            $table->text('suspension_reason')->nullable()->after('status');
            $table->foreignId('suspended_by')->nullable()->after('suspension_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable()->after('suspended_by');

            // P6-2 — proof that the payout account belongs to the seller.
            // One proof at a time: a new upload replaces the previous one.
            $table->string('bank_proof_path')->nullable()->after('bank_verification_status');
            $table->string('bank_proof_original_name')->nullable()->after('bank_proof_path');
            $table->timestamp('bank_proof_uploaded_at')->nullable()->after('bank_proof_original_name');
            $table->foreignId('bank_verified_by')->nullable()->after('bank_proof_uploaded_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('bank_verified_at')->nullable()->after('bank_verified_by');
            $table->text('bank_rejection_reason')->nullable()->after('bank_verified_at');

            $table->index('bank_verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropIndex(['bank_verification_status']);
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropConstrainedForeignId('bank_verified_by');
            $table->dropColumn([
                'suspension_reason',
                'suspended_at',
                'bank_proof_path',
                'bank_proof_original_name',
                'bank_proof_uploaded_at',
                'bank_verified_at',
                'bank_rejection_reason',
            ]);
        });
    }
};
