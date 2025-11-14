<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Wallet;



class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'gender',
        'dob',
        'avatar',
        'is_active',
        'default_shipping_address',
        'default_billing_address',
        'last_login_at',
        'last_order_at',
        'email_verified_at',
        'google_id',
        'google_email',
        'google_avatar',
        'facebook_id',
        'facebook_email',
        'facebook_avatar',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'        => 'datetime',
        'dob'                      => 'date',
        'is_active'                => 'boolean',
        'default_shipping_address' => 'array',
        'default_billing_address'  => 'array',
        'last_login_at'            => 'datetime',
        'last_order_at'            => 'datetime',
        'password'                 => 'hashed',   // 👈 THÊM DÒNG NÀY
        'birthday' => 'date',

    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }
    public function ensureWallet(): Wallet
    {
        return $this->wallet()->firstOrCreate([], [
            'balance' => 0,
            'hold'    => 0,
            'currency' => 'VND',
        ]);
    }
    public function memberTier()
    {
        return $this->hasOne(\App\Models\UserTier::class, 'user_id')->with('tier');
    }
    public function getAvatarUrlAttribute(): string
    {
        // Ưu tiên ảnh do user upload; nếu trống thì dùng ảnh Google
        $a = (string) ($this->avatar ?: $this->google_avatar ?: '');

        // chưa có gì -> fallback pravatar theo email/id
        if ($a === '') {
            $seed = $this->email ?: ('user-' . $this->id);
            return 'https://i.pravatar.cc/120?u=' . urlencode($seed);
        }

        // là URL ngoài -> dùng nguyên
        if (Str::startsWith($a, ['http://', 'https://', '//'])) {
            return $a;
        }

        // là đường dẫn đã có /storage -> bọc asset
        if (Str::startsWith($a, ['/storage', 'storage/'])) {
            return asset($a);
        }

        // path tương đối trong disk public
        return asset('storage/' . ltrim($a, '/'));
    }
}
