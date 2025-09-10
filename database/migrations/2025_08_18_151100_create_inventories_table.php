<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_variant_id')->unique()->constrained()->cascadeOnDelete();
            $t->integer('qty_in_stock')->default(0);
            $t->integer('low_stock_threshold')->default(3);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
