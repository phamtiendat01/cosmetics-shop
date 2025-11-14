<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $t) {
            // tên shade hiển thị: "Ruby Woo", "02 Coral", ...
            if (!Schema::hasColumn('product_variants', 'shade_name')) {
                $t->string('shade_name', 64)->nullable()->after('name');
            }
            // mã màu chuẩn #RRGGBB (cho AR & shade-match)
            if (!Schema::hasColumn('product_variants', 'shade_hex')) {
                $t->string('shade_hex', 12)->nullable()->after('shade_name'); // ví dụ "#C13B4A"
            }
            // loại hiệu ứng AR: lipstick/blush/foundation/eyeshadow/eyebrow...
            if (!Schema::hasColumn('product_variants', 'tryon_effect')) {
                $t->string('tryon_effect', 24)->nullable()->after('shade_hex');
            }
            // độ đậm khi phủ (0..1), mặc định 0.6
            if (!Schema::hasColumn('product_variants', 'tryon_alpha')) {
                $t->decimal('tryon_alpha', 3, 2)->nullable()->after('tryon_effect');
            }
            // bật/tắt try-on cho variant này
            if (!Schema::hasColumn('product_variants', 'tryon_enabled')) {
                $t->boolean('tryon_enabled')->default(false)->after('tryon_alpha');
            }

            // chỉ mục nhẹ cho lọc theo effect/shade
            $t->index(['tryon_effect', 'tryon_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $t) {
            if (Schema::hasColumn('product_variants', 'tryon_enabled')) $t->dropColumn('tryon_enabled');
            if (Schema::hasColumn('product_variants', 'tryon_alpha'))   $t->dropColumn('tryon_alpha');
            if (Schema::hasColumn('product_variants', 'tryon_effect'))  $t->dropColumn('tryon_effect');
            if (Schema::hasColumn('product_variants', 'shade_hex'))     $t->dropColumn('shade_hex');
            if (Schema::hasColumn('product_variants', 'shade_name'))    $t->dropColumn('shade_name');
        });
    }
};
