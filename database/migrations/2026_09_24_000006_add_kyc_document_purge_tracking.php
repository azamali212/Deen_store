<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // P9-2 — the ROW is never deleted, only the file. The row keeps
        // saying a document existed, who reviewed it and when. Erasing the
        // row would destroy the KYC history we must be able to show.
        Schema::table('seller_application_documents', function (Blueprint $table): void {
            $table->timestamp('file_purged_at')->nullable()->after('ai_checked_at');
            $table->index('file_purged_at', 'seller_app_docs_purged_idx');
        });

        Schema::table('seller_document_renewals', function (Blueprint $table): void {
            $table->timestamp('file_purged_at')->nullable()->after('reviewed_at');
            $table->index('file_purged_at', 'seller_doc_renewals_purged_idx');
        });
    }

    public function down(): void
    {
        Schema::table('seller_application_documents', function (Blueprint $table): void {
            $table->dropIndex('seller_app_docs_purged_idx');
            $table->dropColumn('file_purged_at');
        });

        Schema::table('seller_document_renewals', function (Blueprint $table): void {
            $table->dropIndex('seller_doc_renewals_purged_idx');
            $table->dropColumn('file_purged_at');
        });
    }
};
