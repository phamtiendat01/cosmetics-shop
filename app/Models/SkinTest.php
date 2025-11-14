<?php
// app/Models/SkinTest.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkinTest extends Model
{
    protected $table = 'skin_tests';

    // CHO PHÉP GÁN
    protected $fillable = [
        'user_id',
        'session_id',
        'status',
        'budget',
        'metrics_json',
        'recommendation_json',
        'dominant_skin_type',
        'photos_json',
    ];

    protected $casts = [
        'metrics_json' => 'array',
        'recommendation_json' => 'array',
        'photos_json' => 'array',
    ];

    public function photos()
    {
        return $this->hasMany(SkinTestPhoto::class);
    }
}
