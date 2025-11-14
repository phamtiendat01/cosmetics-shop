<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTier extends Model
{
    protected $table = 'user_tiers';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = ['user_id', 'tier_id', 'qualified_at', 'expires_at', 'current_year_spend', 'last_evaluated_at'];
    protected $casts = ['qualified_at' => 'datetime', 'expires_at' => 'datetime', 'last_evaluated_at' => 'datetime'];

    public function tier()
    {
        return $this->belongsTo(MemberTier::class, 'tier_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
