<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('position');                        // hero, homepage_mid, category_top, sidebar, popup
            $t->string('device')->default('all');         // all|desktop|mobile
            $t->string('image');                          // desktop / chung
            $t->string('mobile_image')->nullable();       // ảnh mobile (nếu có)
            $t->string('url')->nullable();
            $t->boolean('open_in_new_tab')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->json('meta_json')->nullable();            // mở rộng nếu cần
            $t->timestamps();

            $t->index(['position', 'device']);
            $t->index(['is_active', 'starts_at', 'ends_at']);
            $t->index(['sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
