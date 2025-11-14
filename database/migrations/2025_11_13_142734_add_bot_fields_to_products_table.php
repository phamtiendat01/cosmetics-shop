<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Loại da phù hợp (JSON array: ['oily', 'dry', 'combination', 'sensitive', 'normal'])
            if (!Schema::hasColumn('products', 'skin_types')) {
                $table->json('skin_types')->nullable()->after('description');
            }
            
            // Vấn đề da (JSON array: ['acne', 'blackheads', 'dark_spots', 'melasma', 'freckles', 'pores', 'aging', 'hydration', 'sunburn'])
            if (!Schema::hasColumn('products', 'concerns')) {
                $table->json('concerns')->nullable()->after('skin_types');
            }
            
            // Thành phần chính (JSON array: ['hyaluronic_acid', 'niacinamide', 'retinol', 'vitamin_c', 'salicylic_acid', ...])
            if (!Schema::hasColumn('products', 'ingredients')) {
                $table->json('ingredients')->nullable()->after('concerns');
            }
            
            // Công dụng chính (text)
            if (!Schema::hasColumn('products', 'benefits')) {
                $table->text('benefits')->nullable()->after('ingredients');
            }
            
            // Hướng dẫn sử dụng (text)
            if (!Schema::hasColumn('products', 'usage_instructions')) {
                $table->text('usage_instructions')->nullable()->after('benefits');
            }
            
            // Độ tuổi phù hợp (string: 'teen', 'adult', 'mature', 'all')
            if (!Schema::hasColumn('products', 'age_range')) {
                $table->string('age_range', 20)->nullable()->after('usage_instructions');
            }
            
            // Giới tính (string: 'male', 'female', 'unisex')
            if (!Schema::hasColumn('products', 'gender')) {
                $table->string('gender', 20)->nullable()->default('unisex')->after('age_range');
            }
            
            // Loại sản phẩm (string: 'serum', 'cream', 'toner', 'cleanser', 'moisturizer', 'sunscreen', 'mask', 'essence', 'eye_cream', 'other')
            if (!Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type', 50)->nullable()->after('gender');
            }
            
            // Kết cấu (string: 'gel', 'cream', 'liquid', 'foam', 'oil', 'balm', 'powder', 'spray')
            if (!Schema::hasColumn('products', 'texture')) {
                $table->string('texture', 30)->nullable()->after('product_type');
            }
            
            // Chỉ số chống nắng (integer, chỉ cho sunscreen)
            if (!Schema::hasColumn('products', 'spf')) {
                $table->unsignedTinyInteger('spf')->nullable()->after('texture');
            }
            
            // Không mùi (boolean)
            if (!Schema::hasColumn('products', 'fragrance_free')) {
                $table->boolean('fragrance_free')->default(false)->after('spf');
            }
            
            // Không test trên động vật (boolean)
            if (!Schema::hasColumn('products', 'cruelty_free')) {
                $table->boolean('cruelty_free')->default(false)->after('fragrance_free');
            }
            
            // Thuần chay (boolean)
            if (!Schema::hasColumn('products', 'vegan')) {
                $table->boolean('vegan')->default(false)->after('cruelty_free');
            }
            
            // Thêm index cho các trường thường query
            $table->index('skin_types');
            $table->index('product_type');
            $table->index('gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'skin_types',
                'concerns',
                'ingredients',
                'benefits',
                'usage_instructions',
                'age_range',
                'gender',
                'product_type',
                'texture',
                'spf',
                'fragrance_free',
                'cruelty_free',
                'vegan',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
