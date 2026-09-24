<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_application_documents', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('seller_application_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('document_type', 30);

            // Path on the PRIVATE 'local' disk (D7) — never exposed by the API.
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');

            $table->timestamps();

            // One row per type — re-uploading replaces it (D1/C8).
            // Explicit short name: Laravel's auto-generated name would be
            // 71 characters, over MySQL's 64-character identifier limit.
            $table->unique(
                ['seller_application_id', 'document_type'],
                'seller_app_docs_app_type_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_application_documents');
    }
};
