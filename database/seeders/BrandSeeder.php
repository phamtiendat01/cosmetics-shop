<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            "L'Oréal",
            'Maybelline',
            'Innisfree',
            'The Body Shop',
            'La Roche-Posay',
            "Kiehl's",
            'Laneige',
            'MAC',
            'Clinique',
            'Vichy',
            'Nivea',
            'Dove',
            'Hada Labo',
            'SK-II',
            'Estée Lauder'
        ];

        $i = 1;
        foreach ($names as $name) {
            // tạo slug an toàn
            $slug = Str::slug(iconv('UTF-8', 'ASCII//TRANSLIT', $name));

            Brand::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'        => $name,
                    'website'     => null,
                    'sort_order'  => $i++,
                    'is_active'   => true,
                    'logo'        => 'https://via.placeholder.com/150?text=' . urlencode($name),
                    'description' => 'Thương hiệu mỹ phẩm ' . $name,
                ]
            );
        }
    }
}
