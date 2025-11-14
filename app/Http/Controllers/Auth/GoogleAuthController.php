<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        // Nếu local mà hay bị "Invalid state", có thể dùng: ->stateless()
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $g = Socialite::driver('google')->user(); // ->stateless() nếu cần

            $email    = $g->getEmail();
            $googleId = $g->getId();
            $name     = $g->getName() ?: ($g->user['given_name'] ?? 'User');
            $avatar   = $g->getAvatar();

            // 1) Ưu tiên tìm theo google_id
            $user = User::where('google_id', $googleId)->first();

            // 2) Nếu chưa có, liên kết vào tài khoản trùng email (nếu có)
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            // 3) Tạo mới nếu vẫn chưa có user
            if (!$user) {
                $user = new User();
                $user->name = $name;
                $user->email = $email ?: 'u.' . Str::uuid() . '@no-email.local';
                $user->password = Str::random(32);   // cast "hashed" sẽ tự hash
                $user->email_verified_at = now();    // Google đã verify email
            }

            // 4) Cập nhật thông tin Google (không ghi đè avatar thủ công nếu đã có)
            $user->google_id     = $googleId;
            $user->google_email  = $email;
            $user->google_avatar = $avatar;

            // Nếu user chưa từng có avatar do mình upload thì cho tạm avatar Google
            if (empty($user->avatar) && $avatar) {
                $user->avatar = $avatar;
            }

            $user->save();

            // 5) Đăng nhập và chuyển hướng
            Auth::login($user, true);
            return redirect()->intended('/');
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->withErrors([
                'email' => 'Đăng nhập Google thất bại. Vui lòng thử lại.',
            ]);
        }
    }
}
