<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Banner extends Model
{
    const POSITIONS = [
        'hero'         => 'Hero trang chủ',
        'homepage_mid' => 'Giữa trang chủ',
        'category_top' => 'Đầu trang danh mục',
        'sidebar'      => 'Sidebar',
        'popup'        => 'Popup khuyến mãi',
    ];

    const DEVICES = ['all' => 'Tất cả', 'desktop' => 'Desktop', 'mobile' => 'Mobile'];

    protected $fillable = [
        'title',
        'position',
        'device',
        'image',
        'mobile_image',
        'url',
        'open_in_new_tab',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
        'meta_json',
    ];

    protected $casts = [
        'open_in_new_tab' => 'bool',
        'is_active'       => 'bool',
        'starts_at'       => 'datetime',
        'ends_at'         => 'datetime',
        'meta_json'       => 'array',
    ];

    # Đang trong khoảng hiệu lực & bật cờ
    public function scopeVisibleNow(Builder $q): Builder
    {
        $now = now();
        return $q->where('is_active', 1)
            ->where(function ($w) use ($now) {
                $w->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($w) use ($now) {
                $w->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeKeyword(Builder $q, ?string $kw): Builder
    {
        return $q->when($kw, fn($x) => $x->where('title', 'like', "%$kw%"));
    }

    public function getIsRunningNowAttribute(): bool
    {
        $now = now();
        if (!$this->is_active) return false;
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at   && $now->gt($this->ends_at))   return false;
        return true;
    }
}
