<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShippingCarrier extends Model
{
    protected $fillable = ['name', 'code', 'logo', 'supports_cod', 'enabled', 'sort_order', 'config'];
    protected $casts = ['supports_cod' => 'boolean', 'enabled' => 'boolean', 'config' => 'array'];

    public function rates()
    {
        return $this->hasMany(ShippingRate::class, 'carrier_id');
    }
    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) return null;

        $logo = ltrim($this->logo, '/');

        // URL tuyệt đối
        if (Str::startsWith($logo, ['http://', 'https://', '//'])) {
            return $logo;
        }

        // bỏ prefix storage/ nếu có
        $logo = preg_replace('~^storage/~', '', $logo);

        // ảnh lưu trên disk public
        if (Storage::disk('public')->exists($logo)) {
            return Storage::disk('public')->url($logo);
        }

        // fallback: coi là đường dẫn public
        return asset($this->logo);
    }
}
