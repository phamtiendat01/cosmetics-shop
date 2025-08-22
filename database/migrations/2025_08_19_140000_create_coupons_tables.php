<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();                 // MÃ (in HOA, duy nhất)
            $t->string('name')->nullable();                   // tiêu đề dễ đọc
            $t->text('description')->nullable();

            $t->enum('discount_type', ['percent', 'fixed']);   // % hay tiền
            $t->decimal('discount_value', 12, 2);             // 10 = 10% hoặc 10.000đ
            $t->decimal('max_discount', 12, 2)->nullable();   // trần giảm với % (tuỳ chọn)

            $t->decimal('min_order_total', 12, 2)->default(0);

            $t->enum('applied_to', ['order', 'category', 'brand', 'product'])->default('order');
            $t->json('applies_to_ids')->nullable();           // mảng id (category_ids/brand_ids/product_ids)

            $t->boolean('is_stackable')->default(false);      // có cho dùng kèm mã khác không
            $t->boolean('first_order_only')->default(false);  // chỉ đơn đầu
            $t->boolean('is_active')->default(true);

            $t->unsignedInteger('usage_limit')->nullable();         // tổng số lần được dùng
            $t->unsignedInteger('usage_limit_per_user')->nullable(); // số lần mỗi KH

            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();

            $t->timestamps();
            $t->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code_snapshot', 50);
            $t->decimal('discount_amount', 12, 2)->default(0);
            $t->timestamp('redeemed_at')->useCurrent();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
