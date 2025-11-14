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
        Schema::table('coupon_redemptions', function (Blueprint $t) {
            if (!Schema::hasColumn('coupon_redemptions', 'code')) {
                $t->string('code')->nullable()->after('coupon_id');
            }
            if (!Schema::hasColumn('coupon_redemptions', 'shipping_discount_amount')) {
                $t->unsignedBigInteger('shipping_discount_amount')->default(0)
                    ->after('discount_amount');
            }
            if (!Schema::hasColumn('coupon_redemptions', 'user_id')) {
                $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('code');
            }
            if (!Schema::hasColumn('coupon_redemptions', 'order_id')) {
                $t->foreignId('order_id')->constrained()->cascadeOnDelete()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $t) {
            if (Schema::hasColumn('coupon_redemptions', 'shipping_discount_amount')) {
                $t->dropColumn('shipping_discount_amount');
            }
            if (Schema::hasColumn('coupon_redemptions', 'code')) {
                $t->dropColumn('code');
            }
            // tuỳ nhu cầu có drop user_id/order_id hay không
        });
    }
};
