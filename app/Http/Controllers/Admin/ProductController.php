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
    /** Gom danh mục con theo danh mục cha để đổ vào select (optgroup) */
    private function categoryLeafOptionsGrouped(): array
    {
        $parents = Category::whereNull('parent_id')
            ->where('is_active', 1)
            ->orderBy('sort_order')->orderBy('name')
            ->with(['children' => function ($q) {
                $q->where('is_active', 1)->orderBy('sort_order')->orderBy('name');
            }])->get();

        return $parents->mapWithKeys(function ($p) {
            $kids = $p->children->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->all();
            return [$p->name => $kids];
        })->filter()->toArray();
    }

    public function index(Request $r)
    {
        $base = Product::query()
            ->with(['brand:id,name', 'category:id,name'])
            ->withMin('variants as min_price', 'price')
            ->withMax('variants as max_price', 'price')
            ->withSum('inventories as stock', 'qty_in_stock');

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

        $listQ = clone $base;

        if ($kw = trim((string) $r->keyword)) {
            $listQ->where(function ($w) use ($kw) {
                $w->where('name', 'like', "%$kw%")
                    ->orWhere('short_desc', 'like', "%$kw%")
                    ->orWhere('long_desc',  'like', "%$kw%");
            });
        }

        if ($r->filled('brand_id'))    $listQ->where('brand_id', $r->brand_id);
        if ($r->filled('category_id')) $listQ->where('category_id', $r->category_id);

        $status = $r->get('status');
        match ($status) {
            'active'   => $listQ->where('is_active', 1),
            'inactive' => $listQ->where('is_active', 0),
            'low'      => $listQ->whereHas('variants.inventory', fn($q) =>
            $q->whereColumn('qty_in_stock', '<=', 'low_stock_threshold')->where('low_stock_threshold', '>', 0)),
            'out'      => $listQ->whereHas('variants.inventory', fn($q) => $q->where('qty_in_stock', '<=', 0)),
            'novariant' => $listQ->doesntHave('variants'),
            default    => null,
        };

        $sort = $r->get('sort', 'newest');
        match ($sort) {
            'price_asc'  => $listQ->orderBy('min_price')->orderByDesc('id'),
            'price_desc' => $listQ->orderByDesc('max_price')->orderByDesc('id'),
            'stock_desc' => $listQ->orderByDesc('stock')->orderByDesc('id'),
            default      => $listQ->orderByDesc('id'),
        };

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

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        $slugBase = Str::slug($data['slug'] ?? $data['name']);
        $slug     = $this->uniqueSlug($slugBase);

        DB::beginTransaction();
        try {
            $product = new Product();
            $product->name        = $data['name'];
            $product->slug        = $slug;
            $product->brand_id    = $data['brand_id'] ?? null;
            $product->category_id = $data['category_id'] ?? null;

            // ✅ Chỉ dùng short_desc & long_desc
            $product->short_desc  = $data['short_desc'] ?? null;
            $product->long_desc   = $data['long_desc']  ?? null;

            $product->save();

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

            // Ảnh: chấp nhận 'thumbnail' hoặc 'image'
            if ($request->hasFile('thumbnail') || $request->hasFile('image')) {
                $file = $request->file('thumbnail') ?? $request->file('image');
                $path = $file->store('products', 'public');
                $product->thumbnail = $path;
                $product->save();
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
        $product->load([
            'variants.inventory',
            'variants.adjustments' => fn($q) => $q->latest()->limit(5) // show 5 log gần nhất
        ]);


        return view('admin.products.edit', [
            'product'        => $product,
            'brands'         => Brand::orderBy('name')->get(['id', 'name']),
            'categoryGroups' => $this->categoryLeafOptionsGrouped(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $product->name = $data['name'];
            if (!empty($data['slug'])) {
                $product->slug = $this->uniqueSlug(Str::slug($data['slug']), $product->id);
            }
            $product->brand_id    = $data['brand_id'] ?? null;
            $product->category_id = $data['category_id'] ?? null;

            // ✅ Chỉ dùng short_desc & long_desc
            $product->short_desc  = $data['short_desc'] ?? null;
            $product->long_desc   = $data['long_desc']  ?? null;

            $product->save();

            $variants = $this->cleanVariants($data['variants'] ?? []);
            if ($variants->isEmpty()) {
                throw new \Exception('Vui lòng giữ ít nhất 1 biến thể có giá.');
            }

            $keepIds = [];
            foreach ($variants as $v) {
                if (!empty($v['id'])) {
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
                            'low_stock_threshold' => $v['low_stock_threshold'] ?? 0,
                        ]
                    );

                    $keepIds[] = $pv->id;
                } else {
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

            ProductVariant::where('product_id', $product->id)
                ->whereNotIn('id', $keepIds)
                ->get()
                ->each(function ($pv) {
                    $pv->inventory()?->delete();
                    $pv->delete();
                });

            DB::commit();

            // Ảnh mới: chấp nhận 'thumbnail' hoặc 'image'
            if ($request->hasFile('thumbnail') || $request->hasFile('image')) {
                $file = $request->file('thumbnail') ?? $request->file('image');
                $old  = $product->thumbnail;
                $path = $file->store('products', 'public');
                $product->thumbnail = $path;
                $product->save();

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

    public function destroy(Product $product)
    {
        DB::beginTransaction();
        try {
            foreach ($product->variants as $pv) {
                $pv->inventory()?->delete();
                $pv->delete();
            }

            $img = $product->thumbnail;
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

    private function cleanVariants(array $variants)
    {
        return collect($variants)
            ->filter(fn($v) => isset($v['price']) && $v['price'] !== '' && $v['price'] !== null)
            ->map(function ($v) {
                // 🔒 Chỉ ép kiểu nếu field có trong request
                if (array_key_exists('qty_in_stock', $v)) {
                    $v['qty_in_stock'] = (int) $v['qty_in_stock'];
                }
                if (array_key_exists('low_stock_threshold', $v)) {
                    $v['low_stock_threshold'] = (int) $v['low_stock_threshold'];
                }
                return $v;
            })
            ->values();
    }
}
