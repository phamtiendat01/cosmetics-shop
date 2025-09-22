<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotFaq extends Model
{
    protected $fillable = ['pattern', 'answer_md', 'is_active', 'tags'];
    protected $casts = ['is_active' => 'bool', 'tags' => 'array'];
}
