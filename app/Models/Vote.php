<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $fillable = [
        'turn_id',
        'voter_id',
        'success',
    ];

    protected $casts = [
        'success' => 'boolean',
    ];

    public function turn()
    {
        return $this->belongsTo(Turn::class);
    }

    public function voter()
    {
        return $this->belongsTo(GamePlayer::class, 'voter_id');
    }
}
