<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tryon_sessions', function (Blueprint $t) {
            $t->bigIncrements('id');

            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $t->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $t->string('effect', 24)->nullable();     // lipstick / blush / ...
            $t->string('shade_hex', 12)->nullable();  // #RRGGBB user đã chọn
            $t->decimal('match_score', 5, 2)->nullable(); // điểm gợi ý (nếu có)

            $t->json('context')->nullable(); // device, lighting, skin tone estimate...
            $t->timestamps();

            $t->index(['product_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tryon_sessions');
    }
};
