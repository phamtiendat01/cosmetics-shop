<?php
// app/Models/SkinTestPhoto.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkinTestPhoto extends Model
{
    protected $table = 'skin_test_photos';

    protected $fillable = ['skin_test_id', 'path', 'width', 'height', 'face_ok'];

    public function skinTest()
    {
        return $this->belongsTo(SkinTest::class);
    }
}
