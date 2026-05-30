<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $fillable = [
        'type',
        'status',
        'title',
        'team_a_name',
        'team_b_name',
        'winner_team',
    ];

    public function matches()
    {
        return $this->hasMany(GameMatch::class)->orderBy('match_number');
    }
}
