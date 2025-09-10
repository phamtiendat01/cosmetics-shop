<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Chuẩn hóa URL avatar để hiển thị
        $avatar = $user->avatar;
        if ($avatar && !\Illuminate\Support\Str::startsWith($avatar, ['http', '/storage'])) {
            $avatar = asset('storage/' . $avatar); // lưu relative -> show /storage/...
        }

        return view('account.profile', [
            'user'   => $user,
            'avatar' => $avatar,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'  => ['nullable', 'string', 'max:32', Rule::unique('users', 'phone')->ignore($user->id)],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'dob'    => ['nullable', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2MB
            'remove_avatar' => ['sometimes', 'boolean'],
        ], [], [
            'name' => 'Họ tên',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'gender' => 'Giới tính',
            'dob' => 'Ngày sinh',
            'avatar' => 'Ảnh đại diện',
        ]);

        // Nếu tick "xóa ảnh"
        if ($request->boolean('remove_avatar')) {
            if ($user->avatar && !\Illuminate\Support\Str::startsWith($user->avatar, ['http'])) {
                $path = ltrim($user->avatar, '/');
                $path = \Illuminate\Support\Str::startsWith($path, 'storage/') ? \Illuminate\Support\Str::after($path, 'storage/') : $path;
                Storage::disk('public')->delete($path);
            }
            $user->avatar = null;
        }

        // Upload avatar mới
        if ($request->hasFile('avatar')) {
            // Xóa ảnh cũ (nếu là ảnh do mình lưu)
            if ($user->avatar && !\Illuminate\Support\Str::startsWith($user->avatar, ['http'])) {
                $old = ltrim($user->avatar, '/');
                $old = \Illuminate\Support\Str::startsWith($old, 'storage/') ? \Illuminate\Support\Str::after($old, 'storage/') : $old;
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('avatar')->store('avatars', 'public'); // lưu: avatars/xxx.jpg
            $user->avatar = $path;
        }

        // Cập nhật thông tin cơ bản
        $user->name   = $validated['name'];
        if ($user->email !== $validated['email']) {
            $user->email = $validated['email'];
            // nếu cần: $user->email_verified_at = null; // bật lại xác minh email
        }
        $user->phone  = $validated['phone'] ?? null;
        $user->gender = $validated['gender'] ?? null;
        $user->dob    = $validated['dob'] ?? null;

        $user->save();

        return back()->with('status', 'Cập nhật hồ sơ thành công!');
    }
}
