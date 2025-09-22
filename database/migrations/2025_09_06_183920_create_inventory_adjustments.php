<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('product_variant_id')->index();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->integer('delta'); // +10 (nhập) / -5 (hỏng, điều chỉnh xuống)
            $t->string('reason', 100)->nullable(); // 'restock', 'damage', 'manual-set', v.v.
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
