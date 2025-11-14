<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /* ---------- ORDERS ---------- */
        Schema::table('orders', function (Blueprint $t) {
            if (!Schema::hasColumn('orders', 'code'))              $t->string('code', 32)->unique()->after('id');
            if (!Schema::hasColumn('orders', 'payment_method'))    $t->string('payment_method', 20)->nullable()->after('code');
            if (!Schema::hasColumn('orders', 'payment_status'))    $t->string('payment_status', 20)->default('unpaid')->after('payment_method');
            if (!Schema::hasColumn('orders', 'order_status'))      $t->string('order_status', 30)->default('cho_xac_nhan')->after('payment_status');
            if (!Schema::hasColumn('orders', 'subtotal'))          $t->integer('subtotal')->default(0)->after('order_status');
            if (!Schema::hasColumn('orders', 'discount_amount'))   $t->integer('discount_amount')->default(0)->after('subtotal');
            if (!Schema::hasColumn('orders', 'shipping_fee'))      $t->integer('shipping_fee')->default(0)->after('discount_amount');
            if (!Schema::hasColumn('orders', 'grand_total'))       $t->integer('grand_total')->default(0)->after('shipping_fee');
            if (!Schema::hasColumn('orders', 'coupon_code'))       $t->string('coupon_code', 50)->nullable()->after('grand_total');
            if (!Schema::hasColumn('orders', 'note'))              $t->text('note')->nullable();
            if (!Schema::hasColumn('orders', 'placed_at'))         $t->timestamp('placed_at')->nullable();
            if (!Schema::hasColumn('orders', 'confirmed_at'))      $t->timestamp('confirmed_at')->nullable();
            if (!Schema::hasColumn('orders', 'shipped_at'))        $t->timestamp('shipped_at')->nullable();
            if (!Schema::hasColumn('orders', 'delivered_at'))      $t->timestamp('delivered_at')->nullable();
            if (!Schema::hasColumn('orders', 'cancelled_at'))      $t->timestamp('cancelled_at')->nullable();
        });

        /* ---------- ORDER ITEMS (snapshot) ---------- */
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('order_id');
                $t->unsignedBigInteger('product_id')->nullable();
                $t->unsignedBigInteger('variant_id')->nullable();
                $t->string('name');
                $t->string('sku')->nullable();
                $t->integer('price');
                $t->integer('qty');
                $t->integer('total');
                $t->json('meta')->nullable();
                $t->timestamps();
                $t->index('order_id');
            });
        }

        /* ---------- PAYMENT METHODS ---------- */
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $t) {
                $t->id();
                $t->string('code', 20)->unique(); // COD | VIETQR | MOMO | VNPAY | ...
                $t->string('name');
                $t->boolean('is_active')->default(true);
                $t->integer('sort_order')->default(0);
                $t->json('config')->nullable();
                $t->timestamps();
            });
        }

        /* ---------- ORDER PAYMENTS (mỗi giao dịch) ---------- */
        if (!Schema::hasTable('order_payments')) {
            Schema::create('order_payments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('order_id');
                $t->string('method_code', 20);         // COD, VIETQR, MOMO, VNPAY
                $t->integer('amount');
                $t->string('currency', 10)->default('VND');
                $t->string('provider_ref')->nullable(); // mã giao dịch từ cổng
                $t->string('status', 20)->default('pending'); // pending|paid|failed|refunded
                $t->json('meta')->nullable();           // payload/qr_url/redirect_url...
                $t->timestamp('paid_at')->nullable();
                $t->timestamps();
                $t->index(['order_id', 'method_code']);
            });
        }
    }

    public function down(): void
    {
        // Giữ nguyên (không drop để tránh mất dữ liệu)
    }
};
