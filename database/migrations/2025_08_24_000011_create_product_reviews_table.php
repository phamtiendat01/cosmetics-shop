<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $table = 'product_reviews';

    protected $fillable = [
        'product_id',
        'user_id',
        'user_name',
        'rating',
        'content',
        'approved'
    ];

    protected $casts = [
        'approved' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Nếu bạn có bảng phản hồi & “hữu ích”, có thể thêm:
    // public function replies(){ return $this->hasMany(ReviewReply::class)->latest(); }
    // public function helpfuls(){ return $this->hasMany(ReviewReaction::class); }
}
