<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Services\Shipping\DistanceEstimator;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $addresses = UserAddress::where('user_id', $user->id)
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('is_default_billing')
            ->orderByDesc('id')
            ->get();

        $estimates = [];
        foreach ($addresses as $a) {
            $estimates[$a->id] = DistanceEstimator::estimateFee($a->lat, $a->lng, 0);
        }

        return view('account.addresses.index', compact('addresses', 'estimates'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $this->validated($request);

        $addr = new UserAddress($data);
        $addr->user_id = $user->id;

        // Nếu là địa chỉ đầu tiên -> đặt mặc định cả 2
        if (!UserAddress::where('user_id', $user->id)->exists()) {
            $addr->is_default_shipping = true;
            $addr->is_default_billing  = true;
        }

        // Lưu code nếu bảng có cột
        $this->maybeFillCodes($addr, $request);

        $addr->save();

        return redirect()->route('account.addresses.index')
            ->with('status', 'Đã thêm địa chỉ.');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $addr = UserAddress::where('user_id', $user->id)->findOrFail($id);

        $data = $this->validated($request);
        $addr->fill($data);
        $this->maybeFillCodes($addr, $request);
        $addr->save();

        return back()->with('status', 'Đã cập nhật địa chỉ.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $addr = UserAddress::where('user_id', $user->id)->findOrFail($id);
        $addr->delete();

        return back()->with('status', 'Đã xoá địa chỉ.');
    }

    public function makeDefault(Request $request, $id)
    {
        $user = Auth::user();
        $addr = UserAddress::where('user_id', $user->id)->findOrFail($id);

        $type = $request->get('type', 'shipping'); // shipping|billing
        if ($type === 'billing') {
            UserAddress::where('user_id', $user->id)->update(['is_default_billing' => false]);
            $addr->is_default_billing = true;
        } else {
            UserAddress::where('user_id', $user->id)->update(['is_default_shipping' => false]);
            $addr->is_default_shipping = true;
        }
        $addr->save();

        return back()->with('status', 'Đã đặt địa chỉ mặc định.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'phone'    => ['required', 'string', 'max:32'],
            'line1'    => ['required', 'string', 'max:255'],
            'line2'    => ['nullable', 'string', 'max:255'],
            // bắt buộc đủ Tỉnh/Quận/Phường để giảm sai sót
            'ward'     => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'country'  => ['nullable', 'string', 'max:2'],
            // lat/lng có thể để trống: sẽ được tự set ngầm ở UI
            'lat'      => ['nullable', 'numeric', 'between:-90,90'],
            'lng'      => ['nullable', 'numeric', 'between:-180,180'],
        ], [], [
            'name'     => 'Tên người nhận',
            'phone'    => 'Số điện thoại',
            'line1'    => 'Địa chỉ',
            'ward'     => 'Phường/Xã',
            'district' => 'Quận/Huyện',
            'province' => 'Tỉnh/Thành',
        ]);
    }

    protected function maybeFillCodes(UserAddress $addr, Request $request): void
    {
        // Nếu bảng có các cột *_code thì lưu kèm code từ API tỉnh thành
        if (Schema::hasColumn('user_addresses', 'province_code')) {
            $addr->province_code = $request->input('province_code');
        }
        if (Schema::hasColumn('user_addresses', 'district_code')) {
            $addr->district_code = $request->input('district_code');
        }
        if (Schema::hasColumn('user_addresses', 'ward_code')) {
            $addr->ward_code = $request->input('ward_code');
        }
    }
}
