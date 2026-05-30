<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    protected $fillable = [
        'series_id',
        'match_number',
        'blue_bans',
        'red_bans',
        'blue_picks',
        'red_picks',
        'priorities_selected',
        'current_turn_index',
        'status',
        'winner_team',
    ];

    protected $casts = [
        'blue_bans' => 'array',
        'red_bans' => 'array',
        'blue_picks' => 'array',
        'red_picks' => 'array',
        'priorities_selected' => 'array',
    ];

    public function series()
    {
        return $this->belongsTo(Series::class);
    }
}
