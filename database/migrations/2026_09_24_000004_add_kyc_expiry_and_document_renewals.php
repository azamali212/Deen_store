<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table): void {
            // P8-1 — ONLY the dates come out of the encrypted findings.
            // Ciphertext cannot be queried, so a scheduled job could never
            // find an expiring seller through ai_findings. A date is not the
            // secret; the CNIC number is, and that stays encrypted.
            $table->date('cnic_expires_at')->nullable()->after('business_type');
            $table->date('licence_expires_at')->nullable()->after('cnic_expires_at');

            // P8-3 — its own column, never folded into bank_verification_status.
            // Nullable rather than a DB default (C9: the value is set in code).
            // NULL reads as "nothing known", which is VALID, not expired (C27).
            $table->string('kyc_status', 20)->nullable()->after('licence_expires_at');

            // C29 — the daily job emails on a state CHANGE. Without this it
            // would email the same seller every morning for 30 days.
            $table->timestamp('kyc_notified_at')->nullable()->after('kyc_status');

            $table->index('cnic_expires_at');
            $table->index('licence_expires_at');
            $table->index('kyc_status');
        });

        // Existing stores are valid until something says otherwise.
        DB::table('seller_profiles')->whereNull('kyc_status')->update(['kyc_status' => 'valid']);

        // C28 — renewals live HERE, not in seller_application_documents.
        // The application is the frozen record of what an admin actually
        // approved; rewriting it would destroy the audit trail.
        Schema::create('seller_document_renewals', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('seller_profile_id')
                ->constrained('seller_profiles')
                ->cascadeOnDelete();

            $table->string('document_type', 30);

            $table->string('file_path');              // PRIVATE disk (D7)
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');

            // Layer 1 AI result, same shape as seller_application_documents.
            $table->string('ai_status', 20)->nullable();
            $table->text('ai_findings')->nullable();  // encrypted:array -> TEXT
            $table->timestamp('ai_checked_at')->nullable();

            // The one value we lift out of the encrypted findings (P8-1).
            $table->date('extracted_expiry_date')->nullable();

            $table->string('status', 20);             // set in code (C9)
            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            // Short explicit names: the generated ones run past MySQL's
            // 64-character index-name limit on this table.
            $table->index('status', 'seller_doc_renewals_status_idx');
            $table->index(['seller_profile_id', 'status'], 'seller_doc_renewals_store_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_document_renewals');

        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropIndex(['cnic_expires_at']);
            $table->dropIndex(['licence_expires_at']);
            $table->dropIndex(['kyc_status']);
            $table->dropColumn([
                'cnic_expires_at',
                'licence_expires_at',
                'kyc_status',
                'kyc_notified_at',
            ]);
        });
    }
};
