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
        Schema::create('wheel_slices', function (Blueprint $t) {
            $t->id();
            $t->string('label');                    // Text hiển thị trên ô
            $t->enum('type', ['coupon', 'none']);    // Ô thưởng bằng coupon hoặc ô trượt
            $t->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $t->unsignedInteger('weight')->default(1);   // Trọng số để random (tỷ lệ)
            $t->unsignedInteger('stock')->nullable();    // Số lượt phát thưởng còn lại (null = không giới hạn)
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wheel_slices');
    }
};
