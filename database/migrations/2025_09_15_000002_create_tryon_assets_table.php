<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tryon_assets', function (Blueprint $t) {
            $t->bigIncrements('id');
            // effect: 'lipstick', 'blush', 'foundation', 'eyeshadow', 'eyebrow'
            $t->string('effect', 24);
            $t->string('title', 128)->nullable();

            // Các URL asset tĩnh (tuỳ effect có thể không cần mask)
            $t->string('mask_url', 1024)->nullable(); // PNG/SVG mặt nạ
            // Cấu hình JSON: blendMode, smoothing, contour params... (client sẽ đọc)
            $t->json('config')->nullable();

            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->index(['effect', 'is_active']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('tryon_assets');
    }
};
