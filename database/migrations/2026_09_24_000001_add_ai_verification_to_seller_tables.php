<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — AI document verification (BLUEPRINT.txt section 10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_applications', function (Blueprint $table): void {
            // When the applicant accepted automated document checks.
            $table->timestamp('document_processing_consent_at')->nullable()->after('business_type');

            // Layer 2/3 — cross-check result shown to the admin.
            $table->string('ai_risk_level', 10)->nullable()->index()->after('reviewed_at');
            $table->json('ai_report')->nullable()->after('ai_risk_level');
            $table->timestamp('ai_checked_at')->nullable()->after('ai_report');
        });

        Schema::table('seller_application_documents', function (Blueprint $table): void {
            // passed | flagged | skipped (a REJECTED upload is never stored).
            $table->string('ai_status', 20)->nullable()->after('size_bytes');
            // TEXT: holds ENCRYPTED json (extracted CNIC number etc.).
            $table->text('ai_findings')->nullable()->after('ai_status');
            $table->timestamp('ai_checked_at')->nullable()->after('ai_findings');
        });

        Schema::table('seller_profiles', function (Blueprint $table): void {
            // P5-2: matched | mismatch | unknown — payout bank vs the
            // bank statement the admin verified.
            $table->string('bank_verification_status', 20)->nullable()->after('bank_account_last4');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropColumn('bank_verification_status');
        });

        Schema::table('seller_application_documents', function (Blueprint $table): void {
            $table->dropColumn(['ai_status', 'ai_findings', 'ai_checked_at']);
        });

        Schema::table('seller_applications', function (Blueprint $table): void {
            $table->dropIndex(['ai_risk_level']);
            $table->dropColumn(['document_processing_consent_at', 'ai_risk_level', 'ai_report', 'ai_checked_at']);
        });
    }
};
