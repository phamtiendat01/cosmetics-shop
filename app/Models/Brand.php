<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'logo', 'website', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];   // 👈 thêm

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    } // 👈 thêm
}
