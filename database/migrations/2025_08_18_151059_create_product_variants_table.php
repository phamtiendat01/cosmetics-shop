<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('sku')->nullable()->index(); // <-- dùng 'sku'
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
