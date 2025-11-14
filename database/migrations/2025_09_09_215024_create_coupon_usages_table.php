<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('coupon_id')->index();
            $table->string('code', 50)->nullable()->index();
            $table->unsignedInteger('discount')->default(0); // số tiền giảm do coupon cho đơn
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // (tuỳ bạn) có thể thêm FK nếu muốn:
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // $table->foreign('coupon_id')->references('id')->on('coupons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
