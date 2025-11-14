<?php
// app/Http/Controllers/Admin/SettingController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingFormRequest;
use App\Models\Setting;
use Illuminate\Support\Arr;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index');
    }

    public function store(SettingFormRequest $request)
    {
        $data = $request->validated();

        // phẳng mảng theo dot-notation: store.name => ...
        foreach (Arr::dot($data) as $key => $value) {
            // ép kiểu đơn giản cho checkbox/numeric
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            Setting::set($key, $value);
        }

        return back()->with('ok', 'Đã lưu cài đặt.');
    }
}
