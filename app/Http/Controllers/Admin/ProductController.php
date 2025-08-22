<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\{Product, ProductVariant, Inventory, Category, Brand};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductController extends Controller
{
    /* ================= Helpers ================= */

    /** Gom các danh mục CON theo danh mục CHA để đổ vào select (optgroup) */
    private function categoryLeafOptionsGrouped(): array
    {
        $parents = Category::whereNull('parent_id')
            ->where('is_active', 1)
            ->orderBy('sort_order')->orderBy('name')
            ->with(['children' => function ($q) {
                $q->where('is_active', 1)->orderBy('sort_order')->orderBy('name');
            }])->get();

        // Trả về dạng: ['Chăm sóc da mặt' => [ ['id'=>..,'name'=>..], ... ], ...]
        return $parents->mapWithKeys(function ($p) {
            $kids = $p->children->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->all();
            return [$p->name => $kids];
        })->filter()->toArray();
    }

    /* ================= LIST + FILTER ================= */

    public function index(Request $r)
    {
        // Base query để dùng lại nhiều lần
        $base = Product::query()
            ->with(['brand:id,name', 'category:id,name'])
            ->withMin('variants as min_price', 'price')
            ->withMax('variants as max_price', 'price')
            ->withSum('inventories as stock', 'qty_in_stock');

        // ----- Đếm cho tabs (không phụ thuộc filter khác) -----
        $counts = [
            'all'       => (clone $base)->count(),
            'active'    => (clone $base)->where('is_active', 1)->count(),
            'inactive'  => (clone $base)->where('is_active', 0)->count(),
            'low'       => (clone $base)->whereHas('variants.inventory', function ($q) {
                $q->whereColumn('qty_in_stock', '<=', 'low_stock_threshold')
                    ->where('low_stock_threshold', '>', 0);
            })->count(),
            'out'       => (clone $base)->whereHas('variants.inventory', fn($q) => $q->where('qty_in_stock', '<=', 0))->count(),
            'novariant' => (clone $base)->doesntHave('variants')->count(),
        ];

        // ----- Áp filter theo request -----
        $listQ = clone $base;

        // Từ khoá: tên / mô tả
        if ($kw = trim((string) $r->keyword)) {
            $listQ->where(function ($w) use ($kw) {
                $w->where('name', 'like', "%$kw%")
                    ->orWhere('description', 'like', "%$kw%");
            });
        }

        // Lọc theo brand / category
        if ($r->filled('brand_id')) {
            $listQ->where('brand_id', $r->brand_id);
        }
        if ($r->filled('category_id')) {
            $listQ->where('category_id', $r->category_id);
        }

        // Tab trạng thái
        $status = $r->get('status');
        match ($status) {
            'active'   => $listQ->where('is_active', 1),
            'inactive' => $listQ->where('is_active', 0),
            'low'      => $listQ->whereHas('variants.inventory', fn($q) =>
            $q->whereColumn('qty_in_stock', '<=', 'low_stock_threshold')
                ->where('low_stock_threshold', '>', 0)),
            'out'      => $listQ->whereHas('variants.inventory', fn($q) => $q->where('qty_in_stock', '<=', 0)),
            'novariant' => $listQ->doesntHave('variants'),
            default    => null,
        };

        // Sort
        $sort = $r->get('sort', 'newest'); // newest | price_asc | price_desc | stock_desc
        match ($sort) {
            'price_asc'  => $listQ->orderBy('min_price')->orderByDesc('id'),
            'price_desc' => $listQ->orderByDesc('max_price')->orderByDesc('id'),
            'stock_desc' => $listQ->orderByDesc('stock')->orderByDesc('id'),
            default      => $listQ->orderByDesc('id'),
        };

        // Phân trang
        $products = $listQ->paginate(20)->withQueryString();

        return view('admin.products.index', [
            'products'       => $products,
            'counts'         => $counts,
            'filters'        => $r->only('keyword', 'brand_id', 'category_id', 'sort', 'status'),
            'brands'         => Brand::orderBy('name')->get(['id', 'name']),
            'categoryGroups' => $this->categoryLeafOptionsGrouped(),
        ]);
    }

    public function create()
    {
        return view('admin.products.create', [
            'brands'         => Brand::orderBy('name')->get(['id', 'name']),
            'categoryGroups' => $this->categoryLeafOptionsGrouped(),
        ]);
    }

    /* ================= STORE ================= */

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        // Sinh slug duy nhất
        $slugBase = Str::slug($data['slug'] ?? $data['name']);
        $slug     = $this->uniqueSlug($slugBase);

        DB::beginTransaction();
        try {
            // 1) Tạo sản phẩm
            $product = new Product();
            $product->name        = $data['name'];
            $product->slug        = $slug;
            $product->brand_id    = $data['brand_id'] ?? null;
            $product->category_id = $data['category_id'] ?? null;
            $product->description = $data['description'] ?? null;
            $product->save();

            // 2) Biến thể + kho
            $variants = $this->cleanVariants($data['variants'] ?? []);
            if ($variants->isEmpty()) {
                throw new \Exception('Vui lòng thêm ít nhất 1 biến thể có giá.');
            }

            foreach ($variants as $v) {
                $pv = $product->variants()->create([
                    'name'             => $v['name'] ?? null,
                    'sku'              => $v['sku'] ?? null,
                    'price'            => $v['price'],
                    'compare_at_price' => $v['compare_at_price'] ?? null,
                    'is_active'        => 1,
                ]);

                $pv->inventory()->create([
                    'qty_in_stock'        => $v['qty_in_stock'] ?? 0,
                    'low_stock_threshold' => $v['low_stock_threshold'] ?? 0,
                ]);
            }

            DB::commit();

            // 3) Ảnh (ngoài transaction)
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $product->update(['image' => $path]);
            }

            return redirect()->route('admin.products.index')->with('ok', 'Tạo sản phẩm thành công!');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors('Không thể lưu sản phẩm: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Product $product)
    {
        $product->load(['variants.inventory']);

        return view('admin.products.edit', [
            'product'        => $product,
            'brands'         => Brand::orderBy('name')->get(['id', 'name']),
            'categoryGroups' => $this->categoryLeafOptionsGrouped(),
        ]);
    }

    /* ================= UPDATE ================= */

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // 1) Info cơ bản
            $product->name = $data['name'];
            if (!empty($data['slug'])) {
                $product->slug = $this->uniqueSlug(Str::slug($data['slug']), $product->id);
            }
            $product->brand_id    = $data['brand_id'] ?? null;
            $product->category_id = $data['category_id'] ?? null;
            $product->description = $data['description'] ?? null;
            $product->save();

            // 2) Đồng bộ biến thể + kho
            $variants = $this->cleanVariants($data['variants'] ?? []);
            if ($variants->isEmpty()) {
                throw new \Exception('Vui lòng giữ ít nhất 1 biến thể có giá.');
            }

            $keepIds = [];

            foreach ($variants as $v) {
                if (!empty($v['id'])) {
                    // Cập nhật
                    $pv = ProductVariant::where('product_id', $product->id)
                        ->whereKey($v['id'])->firstOrFail();

                    $pv->name             = $v['name'] ?? null;
                    $pv->sku              = $v['sku'] ?? null;
                    $pv->price            = $v['price'];
                    $pv->compare_at_price = $v['compare_at_price'] ?? null;
                    $pv->is_active        = $pv->is_active ?? 1;
                    $pv->save();

                    $pv->inventory()->updateOrCreate(
                        ['product_variant_id' => $pv->id],
                        [
                            'qty_in_stock'        => $v['qty_in_stock'] ?? 0,
                            'low_stock_threshold' => $v['low_stock_threshold'] ?? 0,
                        ]
                    );

                    $keepIds[] = $pv->id;
                } else {
                    // Tạo mới
                    $pv = $product->variants()->create([
                        'name'             => $v['name'] ?? null,
                        'sku'              => $v['sku'] ?? null,
                        'price'            => $v['price'],
                        'compare_at_price' => $v['compare_at_price'] ?? null,
                        'is_active'        => 1,
                    ]);

                    $pv->inventory()->create([
                        'qty_in_stock'        => $v['qty_in_stock'] ?? 0,
                        'low_stock_threshold' => $v['low_stock_threshold'] ?? 0,
                    ]);

                    $keepIds[] = $pv->id;
                }
            }

            // Xoá biến thể không còn
            ProductVariant::where('product_id', $product->id)
                ->whereNotIn('id', $keepIds)
                ->get()
                ->each(function ($pv) {
                    $pv->inventory()?->delete();
                    $pv->delete();
                });

            DB::commit();

            // 3) Ảnh mới
            if ($request->hasFile('image')) {
                $old = $product->image;
                $path = $request->file('image')->store('products', 'public');
                $product->update(['image' => $path]);
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
            }

            return redirect()->route('admin.products.edit', $product)->with('ok', 'Cập nhật sản phẩm thành công!');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors('Không thể cập nhật: ' . $e->getMessage())->withInput();
        }
    }

    /* ================= DESTROY ================= */

    public function destroy(Product $product)
    {
        DB::beginTransaction();
        try {
            foreach ($product->variants as $pv) {
                $pv->inventory()?->delete();
                $pv->delete();
            }

            $img = $product->image;
            $product->delete();

            DB::commit();

            if ($img && Storage::disk('public')->exists($img)) {
                Storage::disk('public')->delete($img);
            }

            return back()->with('ok', 'Đã xoá sản phẩm.');
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors('Không thể xoá: ' . $e->getMessage());
        }
    }

    /* ================= Misc helpers ================= */

    /** Tạo slug duy nhất (bỏ qua $ignoreId nếu có) */
    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base ?: Str::random(8);
        $i = 1;

        $exists = function ($s) use ($ignoreId) {
            $q = Product::where('slug', $s);
            if ($ignoreId) $q->where('id', '!=', $ignoreId);
            return $q->exists();
        };

        while ($exists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /** Lọc bỏ biến thể rỗng giá + ép kiểu tồn kho */
    private function cleanVariants(array $variants)
    {
        return collect($variants)
            ->filter(fn($v) => isset($v['price']) && $v['price'] !== '' && $v['price'] !== null)
            ->map(function ($v) {
                $v['qty_in_stock']        = isset($v['qty_in_stock']) ? (int)$v['qty_in_stock'] : 0;
                $v['low_stock_threshold'] = isset($v['low_stock_threshold']) ? (int)$v['low_stock_threshold'] : 0;
                return $v;
            })
            ->values();
    }
}
