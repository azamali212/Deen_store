<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Personal
            $table->string('username')->nullable()->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->text('bio')->nullable();

            // Avatar
            $table->string('avatar_path')->nullable();
            $table->string('avatar_provider', 30)->default('local');

            // Public Profile
            $table->string('website_url')->nullable();
            $table->string('occupation')->nullable();
            $table->string('company_name')->nullable();
            $table->string('country_code', 10)->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('locale', 10)->default('en');

            // Profile Settings
            $table->enum('profile_visibility', ['public', 'private'])->default('private');
            $table->unsignedTinyInteger('profile_completion')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index('username');
            $table->index('profile_visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
