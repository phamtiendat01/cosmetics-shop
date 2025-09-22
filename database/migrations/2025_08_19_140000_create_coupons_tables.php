<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable()->unique(); // null = auto promotion
            $t->enum('apply_scope', ['order', 'item', 'shipping'])->default('order');
            $t->enum('discount_type', ['percent', 'fixed', 'free_shipping'])->default('fixed'); // free_shipping dùng cho scope shipping

            $t->unsignedInteger('percent')->nullable();        // khi percent
            $t->unsignedBigInteger('amount')->nullable();      // khi fixed (VND)
            $t->unsignedBigInteger('max_discount')->nullable(); // trần giảm (VND)
            $t->unsignedBigInteger('shipping_cap')->nullable(); // trần giảm ship (VND)
            $t->unsignedBigInteger('min_subtotal')->nullable(); // điều kiện ngưỡng (VND)

            $t->boolean('exclude_sale_items')->default(false);

            // giới hạn dùng
            $t->unsignedInteger('usage_limit')->nullable();     // tổng lượt
            $t->unsignedInteger('per_user_limit')->nullable();  // mỗi user
            $t->boolean('first_order_only')->default(false);
            $t->boolean('require_logged_in')->default(true);

            // stacking: none / with_shipping / all
            $t->enum('stacking', ['none', 'with_shipping', 'all'])->default('with_shipping');

            // thời gian
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();

            $t->boolean('is_active')->default(true);

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
