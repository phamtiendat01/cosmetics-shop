<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // 1) Nếu chỉ có variant_sku (chưa có sku) -> rename
            if (
                Schema::hasColumn('product_variants', 'variant_sku')
                && !Schema::hasColumn('product_variants', 'sku')
            ) {
                $table->renameColumn('variant_sku', 'sku');
            }
        });

        // 2) Nếu CẢ HAI cột đều tồn tại -> merge dữ liệu rồi drop variant_sku
        if (
            Schema::hasColumn('product_variants', 'variant_sku')
            && Schema::hasColumn('product_variants', 'sku')
        ) {
            // ưu tiên giữ giá trị hiện có ở sku, nếu null thì lấy variant_sku
            DB::statement("
                UPDATE product_variants 
                SET sku = COALESCE(sku, variant_sku)
            ");

            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('variant_sku');
            });
        }

        // 3) Nếu đã có sku và KHÔNG có variant_sku -> không làm gì
    }

    public function down(): void
    {
        // Down “an toàn”: tạo lại variant_sku nếu chưa có, copy từ sku (nếu muốn)
        if (!Schema::hasColumn('product_variants', 'variant_sku')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->string('variant_sku')->nullable()->after('name');
            });

            if (Schema::hasColumn('product_variants', 'sku')) {
                DB::statement("
                    UPDATE product_variants
                    SET variant_sku = COALESCE(variant_sku, sku)
                ");
            }
        }

        // Tuỳ chọn: nếu bạn thật sự muốn quay lại trạng thái cũ, có thể drop 'sku'.
        // Nhưng thường không cần (tránh mất dữ liệu).
        // if (Schema::hasColumn('product_variants','sku')) {
        //     Schema::table('product_variants', function (Blueprint $table) {
        //         $table->dropColumn('sku');
        //     });
        // }
    }
};
