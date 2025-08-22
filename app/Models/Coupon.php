<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_order_total',
        'applied_to',
        'applies_to_ids',
        'is_stackable',
        'first_order_only',
        'is_active',
        'usage_limit',
        'usage_limit_per_user',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'applies_to_ids' => 'array',
        'is_stackable' => 'bool',
        'first_order_only' => 'bool',
        'is_active' => 'bool',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'discount_value' => 'float',
        'max_discount' => 'float',
        'min_order_total' => 'float',
    ];

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function usedCount(): int
    {
        return (int) $this->redemptions()->count();
    }

    public function isInTimeWindow(?Carbon $now = null): bool
    {
        $now = $now ?: now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at   && $now->gt($this->ends_at))   return false;
        return true;
    }

    /** Kiểm tra đủ điều kiện và tính số tiền được giảm.
     * $cart = [
     *   'subtotal' => 150000,
     *   'items' => [ ['product_id'=>1,'brand_id'=>2,'category_id'=>3,'qty'=>1,'line_total'=>150000], ... ]
     * ]
     * return ['ok'=>true, 'amount'=>30000] hoặc ['ok'=>false, 'error'=>'...']
     */
    public function evaluate(array $cart, ?\App\Models\User $user = null): array
    {
        if (!$this->is_active) return ['ok' => false, 'error' => 'Mã đang tạm khoá'];
        if (!$this->isInTimeWindow()) return ['ok' => false, 'error' => 'Mã chưa hiệu lực hoặc đã hết hạn'];

        // giới hạn lượt dùng tổng
        if ($this->usage_limit && $this->usedCount() >= $this->usage_limit) {
            return ['ok' => false, 'error' => 'Mã đã hết lượt sử dụng'];
        }
        // giới hạn mỗi người
        if ($user && $this->usage_limit_per_user) {
            $uCount = $this->redemptions()->where('user_id', $user->id)->count();
            if ($uCount >= $this->usage_limit_per_user) {
                return ['ok' => false, 'error' => 'Bạn đã dùng mã này đủ số lần'];
            }
        }
        // đơn đầu tiên
        if ($this->first_order_only && $user) {
            $hasOrder = \App\Models\Order::where('user_id', $user->id)->exists();
            if ($hasOrder) return ['ok' => false, 'error' => 'Mã chỉ áp dụng cho đơn đầu tiên'];
        }

        $subtotal = (float)($cart['subtotal'] ?? 0);
        if ($subtotal + 1e-6 < (float)$this->min_order_total) {
            return ['ok' => false, 'error' => 'Chưa đạt giá trị đơn tối thiểu'];
        }

        // lọc phạm vi áp dụng
        $eligibleTotal = $subtotal;
        if ($this->applied_to !== 'order') {
            $ids = collect($this->applies_to_ids ?: []);
            $eligibleTotal = collect($cart['items'] ?? [])->sum(function ($i) use ($ids) {
                return match ($this->applied_to) {
                    'brand'    => $ids->contains($i['brand_id'] ?? null)    ? ($i['line_total'] ?? 0) : 0,
                    'category' => $ids->contains($i['category_id'] ?? null) ? ($i['line_total'] ?? 0) : 0,
                    'product'  => $ids->contains($i['product_id'] ?? null)  ? ($i['line_total'] ?? 0) : 0,
                    default    => 0,
                };
            });
            if ($eligibleTotal <= 0) return ['ok' => false, 'error' => 'Giỏ hàng không nằm trong phạm vi mã'];
        }

        // tính tiền giảm
        $amount = $this->discount_type === 'percent'
            ? round($eligibleTotal * ($this->discount_value / 100))
            : min($eligibleTotal, (float)$this->discount_value);

        if ($this->discount_type === 'percent' && $this->max_discount) {
            $amount = min($amount, (float)$this->max_discount);
        }

        return ['ok' => true, 'amount' => max(0, (float)$amount)];
    }
}
