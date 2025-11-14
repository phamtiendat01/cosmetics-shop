<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCarrierRequest;
use App\Models\ShippingCarrier;
use Illuminate\Http\Request;

class ShippingCarrierController extends Controller
{
    public function index(Request $r)
    {
        $carriers = ShippingCarrier::query()
            ->when($r->search, fn($q, $s) => $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->orderBy('sort_order')->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        // 👉 TRẢ VỀ $carriers (đúng với blade)
        return view('admin.shipping.carriers.index', compact('carriers'));
    }



    public function create()
    {
        return view('admin.shipping.carriers.form', ['carrier' => new ShippingCarrier]);
    }

    public function store(StoreCarrierRequest $req)
    {
        $data = $req->validated();
        $data['supports_cod'] = $req->boolean('supports_cod');
        $data['enabled']      = $req->boolean('enabled');

        if (isset($data['config']) && is_string($data['config'])) {
            $json = json_decode($data['config'], true);
            if (json_last_error() === JSON_ERROR_NONE) $data['config'] = $json;
            else unset($data['config']); // hoặc $data['config'] = [];
        }

        ShippingCarrier::create($data);
        return back()->with('ok', 'Đã tạo đơn vị vận chuyển.');
    }

    public function edit(ShippingCarrier $carrier)
    {
        return view('admin.shipping.carriers.form', compact('carrier'));
    }
    public function update(StoreCarrierRequest $req, ShippingCarrier $carrier)
    {
        $data = $req->validated();
        $data['supports_cod'] = $req->boolean('supports_cod');
        $data['enabled']      = $req->boolean('enabled');

        if (isset($data['config']) && is_string($data['config'])) {
            $json = json_decode($data['config'], true);
            if (json_last_error() === JSON_ERROR_NONE) $data['config'] = $json;
            else unset($data['config']);
        }

        $carrier->update($data);
        return back()->with('ok', 'Đã cập nhật.');
    }
    public function toggle(Request $r, \App\Models\ShippingCarrier $carrier)
    {
        // Nếu form gửi enabled thì dùng giá trị đó, không thì đảo trạng thái
        $carrier->enabled = $r->has('enabled') ? $r->boolean('enabled') : !$carrier->enabled;
        $carrier->save();

        return back()->with('ok', $carrier->enabled ? 'Đã bật đơn vị.' : 'Đã tắt đơn vị.');
    }



    public function destroy(ShippingCarrier $carrier)
    {
        $carrier->delete();
        return back()->with('ok', 'Đã xóa.');
    }
}
