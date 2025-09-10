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
        // Nếu CHƯA có bảng thì mới tạo
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $t) {
                $t->id();
                $t->string('code', 20)->unique();
                $t->string('name');
                $t->boolean('is_active')->default(true);
                $t->integer('sort_order')->default(0);
                $t->json('config')->nullable();
                $t->timestamps();
            });
            return;
        }

        // Nếu bảng đã tồn tại, chỉ đảm bảo các cột cần thiết có mặt
        Schema::table('payment_methods', function (Blueprint $t) {
            if (!Schema::hasColumn('payment_methods', 'code'))       $t->string('code', 20)->unique()->after('id');
            if (!Schema::hasColumn('payment_methods', 'name'))       $t->string('name')->after('code');
            if (!Schema::hasColumn('payment_methods', 'is_active'))  $t->boolean('is_active')->default(true);
            if (!Schema::hasColumn('payment_methods', 'sort_order')) $t->integer('sort_order')->default(0);
            if (!Schema::hasColumn('payment_methods', 'config'))     $t->json('config')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
