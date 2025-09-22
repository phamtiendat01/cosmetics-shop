<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'phone'))            $t->string('phone', 32)->nullable()->after('email');
            if (!Schema::hasColumn('users', 'gender'))           $t->enum('gender', ['male', 'female', 'other'])->nullable()->after('phone');
            if (!Schema::hasColumn('users', 'dob'))              $t->date('dob')->nullable()->after('gender');
            if (!Schema::hasColumn('users', 'avatar'))           $t->string('avatar')->nullable()->after('dob');
            if (!Schema::hasColumn('users', 'is_active'))        $t->boolean('is_active')->default(true)->after('avatar');
            if (!Schema::hasColumn('users', 'email_verified_at')) $t->timestamp('email_verified_at')->nullable()->change(); // nếu có rồi thì bỏ qua
            if (!Schema::hasColumn('users', 'default_shipping_address')) $t->json('default_shipping_address')->nullable()->after('is_active');
            if (!Schema::hasColumn('users', 'default_billing_address'))  $t->json('default_billing_address')->nullable()->after('default_shipping_address');
            if (!Schema::hasColumn('users', 'last_login_at'))    $t->timestamp('last_login_at')->nullable()->after('default_billing_address');
            if (!Schema::hasColumn('users', 'last_order_at'))    $t->timestamp('last_order_at')->nullable()->after('last_login_at');

            // index hữu ích
            if (!Schema::hasColumn('users', 'phone')) return;
        });

        // Index mềm – tạo nếu chưa có
        try {
            Schema::table('users', fn(Blueprint $t) => $t->unique('phone', 'users_phone_unique'));
        } catch (\Throwable $e) {
        }
        try {
            Schema::table('users', fn(Blueprint $t) => $t->index('is_active', 'users_is_active_idx'));
        } catch (\Throwable $e) {
        }
        try {
            Schema::table('users', fn(Blueprint $t) => $t->index('created_at', 'users_created_at_idx'));
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'last_order_at'))            $t->dropColumn('last_order_at');
            if (Schema::hasColumn('users', 'last_login_at'))            $t->dropColumn('last_login_at');
            if (Schema::hasColumn('users', 'default_billing_address'))  $t->dropColumn('default_billing_address');
            if (Schema::hasColumn('users', 'default_shipping_address')) $t->dropColumn('default_shipping_address');
            if (Schema::hasColumn('users', 'is_active'))                $t->dropColumn('is_active');
            if (Schema::hasColumn('users', 'avatar'))                   $t->dropColumn('avatar');
            if (Schema::hasColumn('users', 'dob'))                      $t->dropColumn('dob');
            if (Schema::hasColumn('users', 'gender'))                   $t->dropColumn('gender');
            if (Schema::hasColumn('users', 'phone')) {
                try {
                    $t->dropUnique('users_phone_unique');
                } catch (\Throwable $e) {
                }
                $t->dropColumn('phone');
            }
        });
    }
};
