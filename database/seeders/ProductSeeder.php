<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Product, ProductVariant, Inventory, Brand, Category};
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $mapCat = Category::pluck('id', 'slug');   // 'sua-rua-mat' => id
        $mapBrand = Brand::pluck('id', 'slug');    // 'la-roche-posay' => id

        $items = [
            [
                'name' => 'Sữa rửa mặt dịu nhẹ',
                'brand' => 'la-roche-posay',
                'cat' => 'sua-rua-mat',
                'thumb' => 'https://images.unsplash.com/photo-1585238342028-4bbc3ee0cf32?q=80&w=1200&auto=format&fit=crop',
                'variants' => [
                    ['name' => '50ml', 'sku' => 'SRM-LRP-50', 'price' => 165000, 'stock' => 40],
                    ['name' => '100ml', 'sku' => 'SRM-LRP-100', 'price' => 265000, 'stock' => 30],
                ]
            ],
            [
                'name' => 'Serum Niacinamide 10%',
                'brand' => 'the-ordinary',
                'cat' => 'serum',
                'thumb' => 'https://images.unsplash.com/photo-1610276198568-eb6d0ff53e48?q=80&w=1200&auto=format&fit=crop',
                'variants' => [
                    ['name' => '30ml', 'sku' => 'SRM-TO-30', 'price' => 279000, 'stock' => 25],
                    ['name' => '60ml', 'sku' => 'SRM-TO-60', 'price' => 459000, 'stock' => 10],
                ]
            ],
            [
                'name' => 'Kem chống nắng SPF50+',
                'brand' => 'innisfree',
                'cat' => 'chong-nang',
                'thumb' => 'https://images.unsplash.com/photo-1629198735660-58c70c64afdd?q=80&w=1200&auto=format&fit=crop',
                'variants' => [
                    ['name' => 'Tuýp 50ml', 'sku' => 'KCN-IF-50', 'price' => 245000, 'stock' => 35],
                ]
            ],
        ];

        foreach ($items as $it) {
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($it['name'])],
                [
                    'brand_id' => $mapBrand[$it['brand']] ?? null,
                    'category_id' => $mapCat[$it['cat']] ?? null,
                    'name' => $it['name'],
                    'thumbnail' => $it['thumb'],
                    'short_desc' => 'Sản phẩm mẫu cho trang chủ.',
                    'is_active' => true,
                    'has_variants' => true,
                    'skin_types' => json_encode(['oily', 'dry', 'combination']),
                    'concerns' => json_encode(['acne', 'hydration'])
                ]
            );

            foreach ($it['variants'] as $v) {
                $variant = ProductVariant::updateOrCreate(
                    ['variant_sku' => $v['sku']],
                    [
                        'product_id' => $product->id,
                        'name' => $v['name'],
                        'price' => $v['price'],
                        'compare_at_price' => null,
                        'weight_grams' => 0,
                        'is_active' => true
                    ]
                );
                Inventory::updateOrCreate(
                    ['product_variant_id' => $variant->id],
                    ['qty_in_stock' => $v['stock'], 'low_stock_threshold' => 3]
                );
            }
        }
    }
}
