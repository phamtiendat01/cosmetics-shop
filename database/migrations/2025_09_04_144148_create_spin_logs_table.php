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
        Schema::create('spin_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('wheel_slice_id')->nullable()->constrained('wheel_slices')->nullOnDelete();
            $t->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $t->string('coupon_code')->nullable();  // code thực tế nhận được (nếu thưởng là coupon dùng chung)
            $t->json('meta')->nullable();
            $t->timestamps();

            $t->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spin_logs');
    }
};
