<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function challenges()
    {
        return $this->hasMany(Challenge::class);
    }

    public function games()
    {
        return $this->belongsToMany(Game::class, 'game_category');
    }
}
