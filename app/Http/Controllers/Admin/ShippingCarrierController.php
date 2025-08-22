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
        $q = ShippingCarrier::query()
            ->when($r->search, fn($q, $s) => $q->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%"))
            ->orderBy('sort_order')->orderBy('id', 'desc')
            ->paginate(12);
        return view('admin.shipping.carriers.index', compact('q'));
    }

    public function create()
    {
        return view('admin.shipping.carriers.form', ['carrier' => new ShippingCarrier]);
    }
    public function store(StoreCarrierRequest $req)
    {
        $data = $req->validated();
        $data['supports_cod'] = $req->boolean('supports_cod');
        $data['enabled'] = $req->boolean('enabled');
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
        $data['enabled'] = $req->boolean('enabled');
        $carrier->update($data);
        return back()->with('ok', 'Đã cập nhật.');
    }

    public function destroy(ShippingCarrier $carrier)
    {
        $carrier->delete();
        return back()->with('ok', 'Đã xóa.');
    }
}
