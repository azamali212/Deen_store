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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->nullable(); // Nullable for guest users
            $table->string('cart_token')->nullable(); // For guest users
            $table->decimal('total_price', 10, 2)->default(0.00); // ✅ Add this column
            $table->integer('total_quantity')->default(0); // ✅ Add this column
            $table->decimal('discount_amount', 10, 2)->default(0.00); // Cart-level discount
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->string('currency')->default('USD'); // Multi-currency support
            $table->enum('status', ['active', 'abandoned', 'checked_out'])->default('abandoned');
            $table->timestamp('expires_at')->nullable(); // Auto-abandon carts after a period
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
