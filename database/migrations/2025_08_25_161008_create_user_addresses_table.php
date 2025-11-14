<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_addresses')) {
            Schema::create('user_addresses', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->string('name')->nullable();      // tên người nhận
                $t->string('phone', 32)->nullable();
                $t->string('line1')->nullable();
                $t->string('line2')->nullable();
                $t->string('ward')->nullable();
                $t->string('district')->nullable();
                $t->string('province')->nullable();
                $t->string('country')->default('VN');
                $t->decimal('lat', 10, 7)->nullable();
                $t->decimal('lng', 10, 7)->nullable();
                $t->boolean('is_default_shipping')->default(false);
                $t->boolean('is_default_billing')->default(false);
                $t->timestamps();

                $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        } else {
            Schema::table('user_addresses', function (Blueprint $t) {
                if (!Schema::hasColumn('user_addresses', 'lat')) $t->decimal('lat', 10, 7)->nullable()->after('country');
                if (!Schema::hasColumn('user_addresses', 'lng')) $t->decimal('lng', 10, 7)->nullable()->after('lat');
                if (!Schema::hasColumn('user_addresses', 'is_default_shipping')) $t->boolean('is_default_shipping')->default(false);
                if (!Schema::hasColumn('user_addresses', 'is_default_billing'))  $t->boolean('is_default_billing')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
