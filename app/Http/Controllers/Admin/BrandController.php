<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /** Tạo slug duy nhất */
    protected function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        $i = 0;
        do {
            $try = $i ? "$slug-$i" : $slug;
            $exists = Brand::where('slug', $try)
                ->when($ignoreId, fn($q) => $q->whereKeyNot($ignoreId))
                ->exists();
            $i++;
        } while ($exists);
        return $try;
    }

    public function index(Request $r)
    {
        // Base để đếm tabs
        $base = Brand::query()->withCount('products');

        $counts = [
            'all'     => (clone $base)->count(),
            'active'  => (clone $base)->where('is_active', 1)->count(),
            'inactive' => (clone $base)->where('is_active', 0)->count(),
            'has'     => (clone $base)->has('products')->count(),
            'empty'   => (clone $base)->doesntHave('products')->count(),
        ];

        // List + filter + tab
        $q = Brand::query()
            ->withCount('products')
            ->when($r->keyword, function ($qq) use ($r) {
                $kw = trim($r->keyword);
                $qq->where(function ($w) use ($kw) {
                    $w->where('name', 'like', "%$kw%")
                        ->orWhere('slug', 'like', "%$kw%");
                });
            });

        // Áp tab
        $status = $r->get('status');
        match ($status) {
            'active'   => $q->where('is_active', 1),
            'inactive' => $q->where('is_active', 0),
            'has'      => $q->has('products'),
            'empty'    => $q->doesntHave('products'),
            default    => null,
        };

        $q->orderBy('sort_order')->orderBy('name');

        $brands = $q->paginate(15)->withQueryString();

        return view('admin.brands.index', [
            'brands'  => $brands,
            'filters' => $r->only('keyword', 'status'),
            'counts'  => $counts,
        ]);
    }


    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request)
    {
        $data = $request->validated();

        // slug
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name']);

        // mặc định hiển thị
        $data['is_active'] = (bool)($data['is_active'] ?? true);

        // upload logo (nếu có)
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        Brand::create($data);

        return redirect()->route('admin.brands.index')->with('ok', 'Tạo thương hiệu thành công!');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $data = $request->validated();

        // xử lý slug
        if (isset($data['slug']) && $data['slug'] !== $brand->slug) {
            $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name'], $brand->id);
        } elseif (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['name'], $brand->id);
        }

        $data['is_active'] = (bool)($data['is_active'] ?? false);

        // logo mới?
        if ($request->hasFile('logo')) {
            $new = $request->file('logo')->store('brands', 'public');
            // xoá logo cũ nếu có
            if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
                Storage::disk('public')->delete($brand->logo);
            }
            $data['logo'] = $new;
        }

        $brand->update($data);

        return redirect()->route('admin.brands.edit', $brand)->with('ok', 'Cập nhật thương hiệu thành công!');
    }

    public function destroy(Brand $brand)
    {
        // không xoá nếu còn sản phẩm
        $brand->loadCount('products');
        if ($brand->products_count > 0) {
            return back()->withErrors('Không thể xoá thương hiệu đang có sản phẩm.');
        }

        if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();

        return back()->with('ok', 'Đã xoá thương hiệu.');
    }

    public function toggle(Brand $brand)
    {
        $brand->is_active = !$brand->is_active;
        $brand->save();

        return back()->with('ok', $brand->is_active ? 'Đã bật hiển thị.' : 'Đã ẩn thương hiệu.');
    }

    public function bulk(Request $r)
    {
        $data = $r->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:brands,id'],
            'act'   => ['required', 'in:activate,deactivate,delete'],
        ], [], [
            'ids' => 'Danh sách mục đã chọn',
            'act' => 'Hành động',
        ]);

        $ids = $data['ids'];
        $act = $data['act'];

        if ($act === 'activate' || $act === 'deactivate') {
            Brand::whereIn('id', $ids)->update(['is_active' => $act === 'activate' ? 1 : 0]);
            return back()->with('ok', 'Đã cập nhật trạng thái cho ' . count($ids) . ' thương hiệu.');
        }

        // delete: chỉ xoá brand không có sản phẩm
        $canDelete = Brand::whereIn('id', $ids)->withCount('products')->get()
            ->filter(fn($b) => $b->products_count == 0);

        foreach ($canDelete as $b) {
            if ($b->logo && Storage::disk('public')->exists($b->logo)) {
                Storage::disk('public')->delete($b->logo);
            }
            $b->delete();
        }

        $deleted = $canDelete->count();
        $blocked = count($ids) - $deleted;

        $msg = "Đã xoá {$deleted} thương hiệu.";
        if ($blocked > 0) $msg .= " {$blocked} thương hiệu bị chặn do đang có sản phẩm.";

        return back()->with('ok', $msg);
    }
}
