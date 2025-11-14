<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];   // 👈 thêm

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    }
}
