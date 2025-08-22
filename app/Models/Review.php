<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['product_id', 'user_id', 'rating', 'title', 'content', 'is_approved'];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
