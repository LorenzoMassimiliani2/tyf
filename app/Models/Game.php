<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'code',
        'status',
        'total_turns',
        'starting_difficulty',
        'difficulty_step_turns',
        'candidate_count',
        'current_turn_number',
        'host_player_id',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function players()
    {
        return $this->hasMany(GamePlayer::class);
    }

    public function turns()
    {
        return $this->hasMany(Turn::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'game_category');
    }
}
