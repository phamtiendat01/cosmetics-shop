<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Facades\{Auth, Hash, Password, RateLimiter};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // === LOGIN ===
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $r)
    {
        $r->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
            'remember' => ['nullable', 'boolean'],
        ]);

        // Rate limit theo email|IP
        $key = Str::lower($r->input('email')) . '|' . $r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Bạn thử lại sau {$seconds} giây."
            ]);
        }

        $user = User::where('email', $r->email)->first();

        if (!$user || !Hash::check($r->password, $user->password)) {
            RateLimiter::hit($key, 60); // khoá 60s sau lần sai
            return back()
                ->withErrors(['email' => 'Thông tin đăng nhập không đúng.'])
                ->onlyInput('email');
        }

        // Rehash nếu thuật toán cũ
        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => Hash::make($r->password)])->save();
        }

        RateLimiter::clear($key);

        Auth::login($user, $r->boolean('remember'));
        $r->session()->flash('just_logged_in', true);

        // Về dashboard tài khoản
        return redirect()->intended(route('home'));
    }

    // === REGISTER ===
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $r)
    {
        $r->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'email'                 => ['required', 'email', 'max:190', 'unique:users,email'],
            'password'              => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['required'],
        ]);

        $user = User::create([
            'name'     => $r->name,
            'email'    => $r->email,
            'password' => Hash::make($r->password),
        ]);

        Auth::login($user);

        return redirect()->route('home');
    }

    // === FORGOT PASSWORD ===
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $r)
    {
        $r->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($r->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    // === RESET PASSWORD ===
    public function showResetForm(string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    }

    public function resetPassword(Request $r)
    {
        $r->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['required'],
        ]);

        $status = Password::reset(
            $r->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($r) {
                $user->forceFill([
                    'password'       => Hash::make($r->password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status == Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    // === LOGOUT ===
    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('home');
    }
}
