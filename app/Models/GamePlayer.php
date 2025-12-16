<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePlayer extends Model
{
    protected $fillable = [
        'game_id',
        'name',
        'avatar_url',
        'token',
        'is_host',
        'status',
        'score',
        'turn_order',
        'last_seen_at',
    ];

    protected $casts = [
        'is_host' => 'boolean',
        'status' => 'string',
        'last_seen_at' => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class, 'voter_id');
    }

    public function turns()
    {
        return $this->hasMany(Turn::class, 'player_id');
    }
}
