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
        Schema::create('member_tiers', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();           // member/silver/gold/platinum
            $t->string('name');
            $t->unsignedInteger('min_spend_year');  // VND - ngưỡng chi tiêu năm dương lịch
            $t->decimal('point_multiplier', 3, 2)->default(1.00); // 1.00, 1.25, 1.50, 2.00...
            $t->unsignedTinyInteger('monthly_ship_quota')->default(0); // số lần free-ship/tháng
            $t->string('auto_coupon_code')->nullable(); // mã tự gán theo hạng (VD: VIP100)
            $t->json('perks_json')->nullable();     // json mở rộng: birthday_bonus_points, early_access, ...
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_tiers');
    }
};
