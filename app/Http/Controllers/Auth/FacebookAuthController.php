<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class FacebookAuthController extends Controller
{
    public function redirect()
    {
        // Nếu dev bị "Invalid state", có thể thêm ->stateless() tạm thời.
        return Socialite::driver('facebook')
            ->scopes(['email']) // xin email
            ->redirect();
    }

    public function callback()
    {
        try {
            $fbUser = Socialite::driver('facebook')->fields([
                'name',
                'first_name',
                'last_name',
                'email',
                'picture'
            ])->user();

            $facebookId = $fbUser->getId();
            $email      = $fbUser->getEmail(); // có thể null nếu user ẩn email
            $name       = $fbUser->getName() ?: 'Facebook User';
            $avatar     = $fbUser->getAvatar(); // URL ảnh FB

            // 1) Tìm theo facebook_id
            $user = User::where('facebook_id', $facebookId)->first();

            // 2) Nếu chưa có, link theo email
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            // 3) Tạo mới nếu chưa tồn tại
            if (!$user) {
                $user = new User();
                $user->name  = $name;
                $user->email = $email ?: ('fb-' . $facebookId . '@no-email.local');
                $user->password = Str::random(32); // model đã cast hashed => tự hash
                $user->email_verified_at = now();
            }

            // 4) Cập nhật thông tin FB
            $user->facebook_id     = $facebookId;
            $user->facebook_email  = $email ?: $user->facebook_email;
            $user->facebook_avatar = $avatar ?: $user->facebook_avatar;

            if (empty($user->avatar) && $avatar) {
                $user->avatar = $avatar;
            }

            $user->save();

            // 5) Đăng nhập
            Auth::login($user, true);

            return redirect()->intended('/');
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->withErrors([
                'email' => 'Đăng nhập Facebook thất bại. Vui lòng thử lại.',
            ]);
        }
    }
}
