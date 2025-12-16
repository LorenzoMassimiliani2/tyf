<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turn extends Model
{
    protected $fillable = [
        'game_id',
        'player_id',
        'challenge_id',
        'turn_number',
        'difficulty',
        'status',
        'max_score',
        'score_awarded',
        'candidate_challenges',
        'selected_at',
        'completed_at',
    ];

    protected $casts = [
        'candidate_challenges' => 'array',
        'selected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function player()
    {
        return $this->belongsTo(GamePlayer::class, 'player_id');
    }

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
}
