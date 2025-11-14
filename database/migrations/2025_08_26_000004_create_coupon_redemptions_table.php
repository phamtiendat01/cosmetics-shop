<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // BẢO VỆ: nếu đã có bảng thì bỏ qua
        if (Schema::hasTable('coupon_redemptions')) {
            return;
        }

        Schema::create('coupon_redemptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $t->string('code')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('discount_amount')->default(0);
            $t->unsignedBigInteger('shipping_discount_amount')->default(0);
            $t->timestamps();
            $t->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};
