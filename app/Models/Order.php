<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'status',
        'payment_status',
        'payment_method',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'shipping_method',
        'tracking_no',
        'subtotal',
        'discount_total',
        'shipping_fee',
        'tax_total',
        'grand_total',
        'placed_at',
        'notes',
        'tags'
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'tags'             => 'array',
        'placed_at'        => 'datetime',
        'subtotal'         => 'decimal:2',
        'discount_total'   => 'decimal:2',
        'shipping_fee'     => 'decimal:2',
        'tax_total'        => 'decimal:2',
        'grand_total'      => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function events()
    {
        return $this->hasMany(OrderEvent::class)->latest();
    }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /* Labels & badges giống các sàn */
    public const STATUSES = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'processing' => 'Đang xử lý',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã huỷ',
        'refunded' => 'Đã hoàn tiền',
    ];
    public const PAY_STATUSES = [
        'unpaid' => 'Chưa thanh toán',
        'paid' => 'Đã thanh toán',
        'failed' => 'Lỗi thanh toán',
        'refunded' => 'Đã hoàn tiền',
    ];
    public const BADGE_BY_STATUS = [
        'pending' => 'badge-amber',
        'confirmed' => 'badge-green',
        'processing' => 'badge',
        'shipping' => 'badge',
        'completed' => 'badge-green',
        'cancelled' => 'badge-red',
        'refunded' => 'badge-red',
    ];
    public const BADGE_BY_PAY = [
        'unpaid' => 'badge-amber',
        'paid' => 'badge-green',
        'failed' => 'badge-red',
        'refunded' => 'badge-red',
    ];

    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
    public function getPaymentStatusLabelAttribute()
    {
        return self::PAY_STATUSES[$this->payment_status] ?? $this->payment_status;
    }
    public function getStatusBadgeAttribute()
    {
        return self::BADGE_BY_STATUS[$this->status] ?? 'badge';
    }
    public function getPaymentStatusBadgeAttribute()
    {
        return self::BADGE_BY_PAY[$this->payment_status] ?? 'badge';
    }

    public function getAddressTextAttribute(): string
    {
        $a = $this->shipping_address ?? [];
        return implode(', ', array_filter([
            Arr::get($a, 'address'),
            Arr::get($a, 'ward'),
            Arr::get($a, 'district'),
            Arr::get($a, 'province')
        ]));
    }

    /* Scopes common */
    public function scopeKeyword($q, ?string $kw)
    {
        if (!$kw) return $q;
        $kw = "%$kw%";
        return $q->where(fn($w) => $w->where('code', 'like', $kw)->orWhere('customer_name', 'like', $kw)->orWhere('customer_phone', 'like', $kw)->orWhere('customer_email', 'like', $kw));
    }
    public function scopeStatus($q, ?string $s)
    {
        return $s ? $q->where('status', $s) : $q;
    }
    public function scopePayStatus($q, ?string $s)
    {
        return $s ? $q->where('payment_status', $s) : $q;
    }

    /* Tính tổng lại */
    public function recalcTotals(): void
    {
        $this->subtotal = $this->items()->sum('line_total');
        $this->grand_total = max(0, $this->subtotal - $this->discount_total + $this->shipping_fee + $this->tax_total);
        $this->save();
    }

    /* Ghi event timeline */
    public function logEvent(string $type, array $old = null, array $new = null, array $meta = []): void
    {
        $this->events()->create(['type' => $type, 'old' => $old, 'new' => $new, 'meta' => $meta]);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\OrderPayment::class);
    }
}
