<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Champion extends Model
{
    /** @use HasFactory<\Database\Factories\ChampionFactory> */
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
}
