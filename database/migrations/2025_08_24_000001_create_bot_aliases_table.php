<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bot_aliases', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('product_id')->index();
            $t->string('alias');        // người dùng hay gọi
            $t->string('alias_norm');   // slug chuẩn hóa để match nhanh
            $t->unsignedSmallInteger('weight')->default(1); // ưu tiên alias phổ biến
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('bot_aliases');
    }
};
