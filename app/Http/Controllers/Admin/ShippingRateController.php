<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRateRequest;
use App\Models\{ShippingRate, ShippingCarrier, ShippingZone};
use Illuminate\Http\Request;

class ShippingRateController extends Controller
{
    public function index(Request $r)
    {
        $rates = ShippingRate::query()
            ->with(['carrier:id,name,code', 'zone:id,name'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // 👉 Trả đúng $carrierOptions và $zoneOptions dưới dạng id => label
        $carrierOptions = ShippingCarrier::orderBy('sort_order')
            ->get(['id', 'name', 'code'])
            ->mapWithKeys(fn($c) => [$c->id => $c->name . ' (' . $c->code . ')'])
            ->all();

        $zoneOptions = ShippingZone::orderBy('name')
            ->pluck('name', 'id')
            ->all();

        return view('admin.shipping.rates.index', compact('rates', 'carrierOptions', 'zoneOptions'));
    }
    public function create()
    {
        $rate = new ShippingRate();
        $carrierOptions = ShippingCarrier::orderBy('sort_order')->pluck('name', 'id');
        $zoneOptions    = ShippingZone::orderBy('name')->pluck('name', 'id');
        return view('admin.shipping.rates.form', compact('rate', 'carrierOptions', 'zoneOptions'));
    }

    public function edit(ShippingRate $rate)
    {
        $carrierOptions = ShippingCarrier::orderBy('sort_order')->pluck('name', 'id');
        $zoneOptions    = ShippingZone::orderBy('name')->pluck('name', 'id');
        return view('admin.shipping.rates.form', compact('rate', 'carrierOptions', 'zoneOptions'));
    }
    public function store(StoreRateRequest $req)
    {
        $data = $req->validated();
        $data['enabled'] = $req->boolean('enabled');
        ShippingRate::create($data);
        return back()->with('ok', 'Đã thêm biểu phí.');
    }

    public function update(Request $req, ShippingRate $rate)
    {
        // Trường hợp chỉ toggle trạng thái
        if ($req->has('enabled') && !$req->has('name')) {
            $rate->update(['enabled' => $req->boolean('enabled')]);
            return back()->with('ok', 'Đã cập nhật biểu phí.');
        }

        // Cập nhật đầy đủ (giữ nguyên validation từ FormRequest)
        $data = $req->validate((new StoreRateRules)->rules());
        $data['enabled'] = $req->boolean('enabled');
        $rate->update($data);

        return back()->with('ok', 'Đã cập nhật biểu phí.');
    }
    public function toggle(Request $r, \App\Models\ShippingRate $rate)
    {
        $rate->enabled = $r->has('enabled') ? $r->boolean('enabled') : !$rate->enabled;
        $rate->save();

        return back()->with('ok', $rate->enabled ? 'Đã bật biểu phí.' : 'Đã tắt biểu phí.');
    }


    public function destroy(ShippingRate $rate)
    {
        $rate->delete();
        return back()->with('ok', 'Đã xóa biểu phí.');
    }
}
