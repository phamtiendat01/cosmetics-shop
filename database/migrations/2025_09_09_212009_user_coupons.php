<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_coupons')) {
            Schema::create('user_coupons', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->foreignId('coupon_id')->constrained()->cascadeOnDelete();
                $t->string('code', 50)->nullable();
                $t->unsignedInteger('times')->default(1);
                $t->timestamp('saved_at')->nullable();
                $t->timestamps();
                $t->unique(['user_id', 'coupon_id', 'code']);
            });
            return;
        }

        // Nếu bảng đã có, đảm bảo đủ cột
        Schema::table('user_coupons', function (Blueprint $t) {
            if (!Schema::hasColumn('user_coupons', 'code')) {
                $t->string('code', 50)->nullable()->after('coupon_id');
            }
            if (!Schema::hasColumn('user_coupons', 'times')) {
                $t->unsignedInteger('times')->default(1)->after('code');
            }
            if (!Schema::hasColumn('user_coupons', 'saved_at')) {
                $t->timestamp('saved_at')->nullable()->after('times');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_coupons');
    }
};
