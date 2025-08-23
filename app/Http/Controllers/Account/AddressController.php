<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    // GET /account/addresses
    public function index()
    {
        $user = Auth::user();
        return response()->json([
            'shipping' => $user?->default_shipping_address,
            'billing'  => $user?->default_billing_address,
        ]);
    }

    // PUT /account/addresses/default
    public function updateDefault(Request $request)
    {
        $data = $request->validate([
            'type'    => 'required|in:shipping,billing',
            'address' => 'required|array', // {line1, ward, district, province, postal...}
        ]);
        $col = $data['type'] === 'shipping' ? 'default_shipping_address' : 'default_billing_address';
        DB::table('users')->where('id', Auth::id())->update([
            $col => json_encode($data['address']),
            'updated_at' => now(),
        ]);
        return response()->json(['status' => 'ok']);
    }
}
