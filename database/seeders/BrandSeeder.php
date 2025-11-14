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
            'L\'Oréal',
            'Maybelline',
            'Innisfree',
            'The Body Shop',
            'La Roche-Posay',
            'Kiehl\'s',
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

        $i = 0;
        foreach ($names as $name) {
            Brand::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name'       => $name,
                    'website'    => null,
                    'sort_order' => $i++,
                    'is_active'  => true,
                    'logo'       => null, // có thể tự gán link/logo demo sau
                ]
            );
        }
    }
}
