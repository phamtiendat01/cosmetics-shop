<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_coupons', function (Blueprint $t) {
            if (!Schema::hasColumn('user_coupons', 'times')) {
                $t->unsignedInteger('times')->default(1)->after('source');
            }
        });
        // Đảm bảo mọi bản ghi cũ có times = 1
        DB::table('user_coupons')->update(['times' => DB::raw('GREATEST(COALESCE(times,0),1)')]);
    }

    public function down(): void
    {
        Schema::table('user_coupons', function (Blueprint $t) {
            if (Schema::hasColumn('user_coupons', 'times')) {
                $t->dropColumn('times');
            }
        });
    }
};
