<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_vouchers', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();            // Mã
            $t->string('title')->nullable();         // Tên hiển thị
            $t->string('type')->default('shipping'); // để phân biệt nếu sau này gom chung
            $t->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $t->unsignedInteger('amount')->default(0);       // số tiền hoặc %
            $t->unsignedInteger('max_discount')->nullable(); // trần khi percent
            $t->unsignedInteger('min_order')->nullable();    // điều kiện giá trị đơn

            // phạm vi (tuỳ dự án, để trống tạm thời dùng toàn đơn)
            $t->json('regions')->nullable();         // giới hạn tỉnh/thành nếu muốn
            $t->json('carriers')->nullable();        // giới hạn hãng vận chuyển

            // thời gian
            $t->timestamp('start_at')->nullable();
            $t->timestamp('end_at')->nullable();

            // giới hạn lượt
            $t->unsignedInteger('usage_limit')->nullable();    // tổng
            $t->unsignedInteger('per_user_limit')->nullable(); // mỗi user

            $t->boolean('is_active')->default(true);

            // nếu phát hành riêng cho 1 user
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $t->text('note')->nullable();
            $t->timestamps();
        });

        // Bảng log dùng mã (tuỳ chọn, nhưng hữu ích cho đếm lượt)
        Schema::create('shipping_voucher_usages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shipping_voucher_id')->constrained('shipping_vouchers')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('order_code')->nullable(); // gắn đơn
            $t->unsignedInteger('discount')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_voucher_usages');
        Schema::dropIfExists('shipping_vouchers');
    }
};
