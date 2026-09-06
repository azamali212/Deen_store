<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table): void {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 20);

            $table->boolean('is_default')
                ->default(false);

            $table->string('label')
                ->nullable();

            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->string('address_line_1');
            $table->string('address_line_2')
                ->nullable();

            $table->string('city');
            $table->string('state')
                ->nullable();

            $table->string('postal_code')
                ->nullable();

            $table->string('country_code', 10);

            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'is_default',
            ]);

            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
