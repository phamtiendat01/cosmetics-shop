<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index(Request $r)
    {
        $q = Banner::query()
            ->keyword($r->keyword)
            ->when($r->position, fn($qq) => $qq->where('position', $r->position))
            ->when($r->device, fn($qq) => $qq->where('device', $r->device))
            ->when($r->status === 'active', fn($qq) => $qq->where('is_active', 1))
            ->when($r->status === 'inactive', fn($qq) => $qq->where('is_active', 0))
            ->orderBy('position')
            ->orderBy('sort_order')
            ->orderByDesc('id');

        $banners = $q->paginate(12)->withQueryString();

        return view('admin.banners.index', [
            'banners'   => $banners,
            'filters'   => $r->only('keyword', 'position', 'device', 'status'),
            'positions' => Banner::POSITIONS,
            'devices'   => Banner::DEVICES,
        ]);
    }

    public function create()
    {
        return view('admin.banners.create', [
            'positions' => Banner::POSITIONS,
            'devices'   => Banner::DEVICES,
        ]);
    }

    public function store(StoreBannerRequest $request)
    {
        $data = $request->validated();

        // lưu file vào disk public -> trả path 'banners/xxx.jpg'
        $path = $request->file('image')->store('banners', 'public');
        // lưu xuống DB dạng URL public: 'storage/banners/xxx.jpg'
        $data['image'] = Storage::url($path);

        if ($request->hasFile('mobile_image')) {
            $m = $request->file('mobile_image')->store('banners', 'public');
            $data['mobile_image'] = Storage::url($m);
        }

        $data['open_in_new_tab'] = (bool)($data['open_in_new_tab'] ?? false);
        $data['sort_order']      = $data['sort_order'] ?? 0;

        Banner::create($data);

        return redirect()->route('admin.banners.index')->with('ok', 'Tạo banner thành công!');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', [
            'banner'    => $banner,
            'positions' => Banner::POSITIONS,
            'devices'   => Banner::DEVICES,
        ]);
    }

    public function update(UpdateBannerRequest $request, Banner $banner)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($p = $this->publicPathFromUrl($banner->image)) {
                Storage::disk('public')->delete($p);   // chỉ xoá khi có path
            }
            $path = $request->file('image')->store('banners', 'public');
            $data['image'] = Storage::url($path);      // lưu URL public: storage/...
        }

        if ($request->hasFile('mobile_image')) {
            if ($p = $this->publicPathFromUrl($banner->mobile_image)) {
                Storage::disk('public')->delete($p);
            }
            $m = $request->file('mobile_image')->store('banners', 'public');
            $data['mobile_image'] = Storage::url($m);
        }

        $data['open_in_new_tab'] = (bool)($data['open_in_new_tab'] ?? false);
        $data['sort_order']      = $data['sort_order'] ?? 0;

        $banner->update($data);

        return redirect()->route('admin.banners.edit', $banner)->with('ok', 'Cập nhật banner thành công!');
    }

    public function destroy(Banner $banner)
    {
        $toDelete = array_values(array_filter([
            $this->publicPathFromUrl($banner->image),
            $this->publicPathFromUrl($banner->mobile_image),
        ]));

        if (!empty($toDelete)) {
            Storage::disk('public')->delete($toDelete);
        }

        $banner->delete();

        // ⬇️ QUAN TRỌNG: về index, không dùng back()
        return redirect()->route('admin.banners.index')
            ->with('ok', 'Đã xoá banner.');
    }


    // Bật/tắt nhanh
    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        return back()->with('ok', 'Đã cập nhật trạng thái.');
    }

    /** Chuyển URL 'storage/banners/xxx.jpg' -> 'banners/xxx.jpg' để xóa trong disk public */
    private function publicPathFromUrl(?string $url): ?string
    {
        if (!$url) return null;
        // 'storage/...', '/storage/...' -> loại phần 'storage/' và dấu '/'
        $trimmed = ltrim(str_replace(['storage/', '/storage/'], '', $url), '/');
        // Nếu DB cũ đã lưu sẵn 'banners/xxx.jpg' thì vẫn trả về nguyên xi
        return $trimmed ?: null;
    }
}
