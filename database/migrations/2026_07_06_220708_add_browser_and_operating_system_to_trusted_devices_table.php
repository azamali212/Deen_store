<?php

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
        Schema::table('trusted_devices', function (Blueprint $table): void {
            $table->string('browser')
                ->nullable()
                ->after('device_name');
            $table->string('operating_system')
                ->nullable()
                ->after('browser');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trusted_devices', function (Blueprint $table): void {
            $table->dropColumn([
                'browser',
                'operating_system',
            ]);
        });
    }
};
