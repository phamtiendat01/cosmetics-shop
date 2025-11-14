<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Lưu VND theo đơn vị "đồng" (integer) để tránh sai số
            $t->bigInteger('balance')->default(0);
            $t->bigInteger('hold')->default(0);
            $t->string('currency', 8)->default('VND');
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
