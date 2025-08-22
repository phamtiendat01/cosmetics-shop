<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreZoneRequest;
use App\Models\ShippingZone;
use Illuminate\Http\Request;

class ShippingZoneController extends Controller
{
    public function index()
    {
        $zones = ShippingZone::orderBy('id', 'desc')->paginate(20);
        return view('admin.shipping.zones.index', compact('zones'));
    }
    public function create()
    {
        return view('admin.shipping.zones.form', ['zone' => new ShippingZone]);
    }
    public function store(StoreZoneRequest $req)
    {
        $data = $req->validated();
        $data['enabled'] = $req->boolean('enabled');
        ShippingZone::create($data);
        return back()->with('ok', 'Đã tạo khu vực.');
    }
    public function edit(ShippingZone $zone)
    {
        return view('admin.shipping.zones.form', compact('zone'));
    }
    public function update(StoreZoneRequest $req, ShippingZone $zone)
    {
        $data = $req->validated();
        $data['enabled'] = $req->boolean('enabled');
        $zone->update($data);
        return back()->with('ok', 'Đã cập nhật khu vực.');
    }
    public function destroy(ShippingZone $zone)
    {
        $zone->delete();
        return back()->with('ok', 'Đã xóa.');
    }
}
