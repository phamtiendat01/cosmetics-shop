<?php

// database/migrations/2025_08_19_000001_create_products_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('thumbnail')->nullable();
    $table->string('short_desc')->nullable();
    $table->text('description')->nullable();   // ✅ thêm dòng này
    $table->boolean('is_active')->default(true);
    $table->boolean('has_variants')->default(false);
    $table->json('skin_types')->nullable();
    $table->json('concerns')->nullable();
    $table->timestamps();
});


    }
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
