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
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->string('last_known_location')->nullable()->after('last_login_ip');
            $table->decimal('latitude', 10, 6)->nullable()->after('last_known_location');
            $table->decimal('longitude', 10, 6)->nullable()->after('latitude');
            $table->timestamp('last_location_updated_at')->nullable()->after('longitude');
            
            // Add indexes for location-based queries
            $table->index(['latitude', 'longitude']);
            $table->index('last_location_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_login_ip',
                'last_known_location', 
                'latitude',
                'longitude',
                'last_location_updated_at'
            ]);
        });
    }
};
