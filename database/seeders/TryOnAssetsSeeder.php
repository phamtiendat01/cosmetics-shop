<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TryOnAsset;

class TryOnAssetsSeeder extends Seeder
{
    public function run(): void
    {
        // Một preset cho son môi (lipstick)
        TryOnAsset::updateOrCreate(
            ['effect' => 'lipstick', 'title' => 'Default Lip'],
            [
                'mask_url' => null, // để null nếu client dùng face segmentation; có thể để PNG cố định nếu dùng ảnh mẫu
                'config'   => [
                    'blend' => 'multiply',
                    'alpha' => 0.6,
                    'soften' => 0.35,
                ],
                'is_active' => true,
            ]
        );

        // Ví dụ thêm preset mắt (eyeshadow)
        TryOnAsset::updateOrCreate(
            ['effect' => 'eyeshadow', 'title' => 'Soft Shadow'],
            [
                'mask_url' => null,
                'config'   => [
                    'blend' => 'screen',
                    'alpha' => 0.45,
                ],
                'is_active' => true,
            ]
        );
    }
}
