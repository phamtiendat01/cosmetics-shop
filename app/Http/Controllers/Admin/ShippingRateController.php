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
        $carriers = ShippingCarrier::orderBy('sort_order')->get();
        $zones    = ShippingZone::orderBy('name')->get();

        $rates = ShippingRate::with(['carrier', 'zone'])
            ->when($r->carrier_id, fn($q, $v) => $q->where('carrier_id', $v))
            ->when($r->zone_id, fn($q, $v) => $q->where('zone_id', $v))
            ->orderBy('carrier_id')->orderBy('zone_id')->orderBy('base_fee')
            ->paginate(20);

        return view('admin.shipping.rates.index', compact('rates', 'carriers', 'zones'));
    }

    public function store(StoreRateRequest $req)
    {
        $data = $req->validated();
        $data['enabled'] = $req->boolean('enabled');
        ShippingRate::create($data);
        return back()->with('ok', 'Đã thêm biểu phí.');
    }

    public function update(StoreRateRequest $req, ShippingRate $rate)
    {
        $data = $req->validated();
        $data['enabled'] = $req->boolean('enabled');
        $rate->update($data);
        return back()->with('ok', 'Đã cập nhật biểu phí.');
    }

    public function destroy(ShippingRate $rate)
    {
        $rate->delete();
        return back()->with('ok', 'Đã xóa biểu phí.');
    }
}
