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
        if (Schema::hasColumn('orders', 'payment_status')) {
            // Đổi sang VARCHAR để nhận các giá trị: unpaid | pending | paid | failed | refunded
            DB::statement("ALTER TABLE orders 
                MODIFY payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('string', function (Blueprint $table) {
            //
        });
    }
};
