<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_shipping_vouchers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('shipping_voucher_id')->constrained('shipping_vouchers')->cascadeOnDelete();
            $t->string('code'); // snapshot code tại thời điểm nhận
            $t->enum('source', ['game', 'admin', 'other'])->default('game');
            $t->unsignedInteger('times')->default(1); // phòng khi cho phép cộng dồn lượt dùng
            $t->timestamp('saved_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'shipping_voucher_id', 'code'], 'uq_user_shipvoucher');
            $t->index(['user_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_shipping_vouchers');
    }
};
