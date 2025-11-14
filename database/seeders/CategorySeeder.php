<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /** Tạo slug duy nhất (tránh đụng unique index) */
    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        $try  = $slug;
        $i    = 1;
        while (Category::where('slug', $try)->exists()) {
            $try = $slug . '-' . $i++;
        }
        return $try;
    }

    private function make(string $name, ?int $parentId = null, int $sort = 0, bool $active = true): Category
    {
        return Category::create([
            'parent_id'  => $parentId,
            'name'       => $name,
            'slug'       => $this->uniqueSlug($name),
            'sort_order' => $sort,
            'is_active'  => $active,
        ]);
    }

    public function run(): void
    {
        // 5 danh mục cha + 10 danh mục con = 15 mục
        $tree = [
            'Chăm sóc da' => ['Sữa rửa mặt', 'Toner', 'Serum', 'Kem dưỡng ẩm', 'Kem chống nắng'],
            'Trang điểm'  => ['Kem nền', 'Son môi', 'Phấn phủ'],
            'Chăm sóc tóc' => ['Dầu gội', 'Dầu xả'],
            'Cơ thể'      => ['Sữa tắm', 'Sữa dưỡng thể'],
            'Nước hoa'    => ['Eau de Parfum', 'Eau de Toilette'],
        ];

        $sortParent = 0;
        foreach ($tree as $parentName => $children) {
            $parent = $this->make($parentName, null, $sortParent++);
            $sortChild = 0;
            foreach ($children as $child) {
                $this->make($child, $parent->id, $sortChild++);
            }
        }
    }
}
