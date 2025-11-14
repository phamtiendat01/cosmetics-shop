<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bot_docs', function (Blueprint $t) {
            $t->id();
            $t->string('title', 255);
            $t->string('slug', 255)->unique();
            $t->text('content_md');             // nội dung FAQ mở rộng, hướng dẫn, kịch bản, SOP...
            $t->string('tags', 255)->nullable(); // ví dụ: "shipping,store-hours,brand-story"
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // Fulltext để tìm ngữ nghĩa cơ bản (BM25). Yêu cầu MySQL hỗ trợ FULLTEXT cho InnoDB.
        Schema::table('bot_docs', function (Blueprint $t) {
            $t->fullText(['title', 'content_md', 'tags']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_docs');
    }
};
