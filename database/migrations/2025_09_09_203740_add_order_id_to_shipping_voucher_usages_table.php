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
        Schema::table('shipping_voucher_usages', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_voucher_usages', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('user_id');
                // Nếu cần ràng buộc FK:
                // $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_voucher_usages', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_voucher_usages', 'order_id')) {
                // $table->dropForeign(['order_id']); // nếu đã tạo FK
                $table->dropColumn('order_id');
            }
        });
    }
};
