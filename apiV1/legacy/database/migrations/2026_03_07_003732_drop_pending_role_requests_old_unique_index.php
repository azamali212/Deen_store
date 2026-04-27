<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_role_requests', function (Blueprint $table) {
            $table->dropUnique('pending_role_requests_name_created_by_status_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pending_role_requests', function (Blueprint $table) {
            $table->unique(['name', 'created_by', 'status'], 'pending_role_requests_name_created_by_status_unique');
        });
    }
};