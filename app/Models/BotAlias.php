<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotAlias extends Model
{
    protected $fillable = ['product_id', 'alias', 'alias_norm', 'weight'];
}
