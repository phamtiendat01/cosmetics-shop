<?php

<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> bf2500afa4460a6a2533ddb3ee4dc9b80b523577
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
<<<<<<< HEAD
=======
=======
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->tinyInteger('rating')->default(0);
            $table->text('content')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
>>>>>>> 26951689d1eb166ac6660244f4404972363ff21b
>>>>>>> bf2500afa4460a6a2533ddb3ee4dc9b80b523577
