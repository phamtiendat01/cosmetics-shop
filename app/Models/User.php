<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;


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

    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
}
