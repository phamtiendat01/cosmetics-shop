<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = ['name', 'province_codes', 'enabled'];
    protected $casts = ['province_codes' => 'array', 'enabled' => 'boolean'];

    // Helper: check tỉnh có thuộc zone không
    public function containsProvince(string $code): bool
    {
        $arr = $this->province_codes ?? [];
        return in_array($code, $arr, true);
    }
}
