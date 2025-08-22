<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code')->unique(); // mã đơn
            $t->enum('status', ['pending', 'confirmed', 'processing', 'shipping', 'completed', 'cancelled', 'refunded'])->default('pending');
            $t->enum('payment_status', ['unpaid', 'paid', 'failed', 'refunded'])->default('unpaid');
            $t->enum('payment_method', ['COD', 'VNPAY', 'MOMO', 'BANK'])->default('COD');
            $t->string('customer_name');
            $t->string('customer_phone', 32);
            $t->json('shipping_address');
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('discount_total', 12, 2)->default(0);
            $t->decimal('shipping_fee', 12, 2)->default(0);
            $t->decimal('tax_total', 12, 2)->default(0);
            $t->decimal('grand_total', 12, 2)->default(0);
            $t->timestamp('placed_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'payment_status', 'placed_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
