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

        $data['image'] = $request->file('image')->store('banners', 'public');
        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $request->file('mobile_image')->store('banners', 'public');
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
            Storage::disk('public')->delete($banner->image);
            $data['image'] = $request->file('image')->store('banners', 'public');
        }
        if ($request->hasFile('mobile_image')) {
            if ($banner->mobile_image) Storage::disk('public')->delete($banner->mobile_image);
            $data['mobile_image'] = $request->file('mobile_image')->store('banners', 'public');
        }
        $data['open_in_new_tab'] = (bool)($data['open_in_new_tab'] ?? false);
        $data['sort_order']      = $data['sort_order'] ?? 0;

        $banner->update($data);

        return redirect()->route('admin.banners.edit', $banner)->with('ok', 'Cập nhật banner thành công!');
    }

    public function destroy(Banner $banner)
    {
        Storage::disk('public')->delete([$banner->image, $banner->mobile_image]);
        $banner->delete();

        return back()->with('ok', 'Đã xoá banner.');
    }

    // Bật/tắt nhanh
    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        return back()->with('ok', 'Đã cập nhật trạng thái.');
    }
}
