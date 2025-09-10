<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipgame_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('box_no');
            $t->enum('result_type', ['none', 'voucher'])->default('none');
            $t->foreignId('shipping_voucher_id')->nullable()->constrained('shipping_vouchers')->nullOnDelete();
            $t->string('voucher_code')->nullable();
            $t->json('meta')->nullable();
            $t->timestamp('saved_at')->nullable(); // dự phòng nếu sau này muốn “lưu” về ví
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('shipgame_logs');
    }
};
