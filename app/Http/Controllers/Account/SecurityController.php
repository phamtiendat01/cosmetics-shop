<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Schema};
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Str;

class SecurityController extends Controller
{
    public function index()
    {
        // Liệt kê phiên đăng nhập nếu dùng SESSION_DRIVER=database
        $sessions = collect();
        try {
            if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
                $sessions = DB::table('sessions')
                    ->where('user_id', auth()->id())
                    ->orderByDesc('last_activity')
                    ->get()
                    ->map(function ($s) {
                        return (object) [
                            'id'         => $s->id,
                            'ip'         => $s->ip_address,
                            'ua'         => $s->user_agent,
                            'last'       => \Carbon\Carbon::createFromTimestamp($s->last_activity),
                            'is_current' => $s->id === session()->getId(),
                        ];
                    });
            }
        } catch (\Throwable $e) { /* ignore */
        }

        return view('account.security', compact('sessions'));
    }

    public function updatePassword(Request $r)
    {
        $r->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $user = $r->user();
        $user->forceFill([
            'password'       => Hash::make($r->password),
            'remember_token' => Str::random(60),
        ])->save();

        // Hủy phiên trên thiết bị khác sau khi đổi mật khẩu
        auth()->logoutOtherDevices($r->current_password);

        return back()->with('status', 'Đã cập nhật mật khẩu.');
    }

    public function updateEmail(Request $r)
    {
        $r->validate([
            'email'            => ['required', 'email', 'max:190', 'unique:users,email,' . $r->user()->id],
            'current_password' => ['required', 'current_password'],
        ]);

        $r->user()->forceFill(['email' => $r->email])->save();

        return back()->with('status', 'Đã cập nhật email.');
    }

    public function logoutOthers(Request $r)
    {
        $r->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        auth()->logoutOtherDevices($r->current_password);

        return back()->with('status', 'Đã đăng xuất khỏi các thiết bị khác.');
    }
}
