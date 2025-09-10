<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $t->string('product_name_snapshot');
            $t->string('variant_name_snapshot')->nullable();
            $t->decimal('unit_price', 12, 2);
            $t->unsignedInteger('qty');
            $t->decimal('line_total', 12, 2);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
