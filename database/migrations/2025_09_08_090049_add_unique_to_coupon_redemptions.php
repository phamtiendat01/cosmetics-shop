<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('coupon_redemptions')) return;

        $hasCouponId     = Schema::hasColumn('coupon_redemptions', 'coupon_id');
        $hasCodeSnapshot = Schema::hasColumn('coupon_redemptions', 'code_snapshot');

        Schema::table('coupon_redemptions', function (Blueprint $t) use ($hasCouponId, $hasCodeSnapshot) {
            // 1) Bỏ unique cũ nếu có
            if ($this->indexExists('coupon_redemptions', 'cr_order_code_unique')) {
                $t->dropUnique('cr_order_code_unique');
            }

            // 2) Tạo unique CHUẨN theo service (order_id, coupon_id, code_snapshot)
            if ($hasCouponId && $hasCodeSnapshot) {
                if (!$this->indexExists('coupon_redemptions', 'cr_order_coupon_code_uq')) {
                    $t->unique(['order_id', 'coupon_id', 'code_snapshot'], 'cr_order_coupon_code_uq');
                }
            } else {
                // Fallback: nếu thiếu cột, dùng (order_id, code) như bạn đang có
                if (!$this->indexExists('coupon_redemptions', 'cr_order_code_unique')) {
                    $t->unique(['order_id', 'code'], 'cr_order_code_unique');
                }
            }
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        // MySQL
        return collect(DB::select("SHOW INDEX FROM `$table`"))->pluck('Key_name')->contains($name);
    }

    public function down(): void
    {
        if (!Schema::hasTable('coupon_redemptions')) return;

        Schema::table('coupon_redemptions', function (Blueprint $t) {
            // Drop cả hai nếu tồn tại
            if ($this->indexExists('coupon_redemptions', 'cr_order_coupon_code_uq')) {
                $t->dropUnique('cr_order_coupon_code_uq');
            }
            if ($this->indexExists('coupon_redemptions', 'cr_order_code_unique')) {
                $t->dropUnique('cr_order_code_unique');
            }
        });
    }
};
