<?php

namespace App\Models;

use Database\Factories\ChampionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Champion extends Model
{
    /** @use HasFactory<ChampionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'secondary_role',
        'image_url',
        'is_priority',
    ];

    protected $casts = [
        'is_priority' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(fn () => Cache::forget('champions_list_all'));
        static::deleted(fn () => Cache::forget('champions_list_all'));
    }
}
