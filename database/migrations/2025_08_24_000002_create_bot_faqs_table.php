<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bot_faqs', function (Blueprint $t) {
            $t->id();
            $t->string('pattern');      // regex hoặc từ khóa, vd: "(phí ship|giao hàng)"
            $t->text('answer_md');      // trả lời markdown gọn gàng
            $t->boolean('is_active')->default(true);
            $t->json('tags')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('bot_faqs');
    }
};
