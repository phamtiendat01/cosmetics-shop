<?php
// app/Models/SkinProfile.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkinProfile extends Model
{
    protected $table = 'skin_profiles';

    // Cho phép gán hàng loạt
    protected $fillable = [
        'user_id',
        'skin_test_id',
        'dominant_skin_type',
        'metrics_json',
        'routine_json',
        'photos_json',
        'note'
    ];
    protected $casts = [
        'metrics_json' => 'array',
        'routine_json' => 'array',
        'photos_json'  => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skinTest()
    {
        return $this->belongsTo(SkinTest::class);
    }
}
