<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomQuestion extends Model
{
    protected $fillable = [
        'room_id',
        'master_question_id',
        'status',
        'is_bought',
        'buyer_team_id',
        'timer_phase',
        'timer_expires_at',
        'global_timer_expires_at',
        'is_paused',
        'paused_global_remaining',
        'paused_phase_remaining',
    ];

    protected $casts = [
        'timer_expires_at' => 'datetime',
        'global_timer_expires_at' => 'datetime',
        'is_paused' => 'boolean',
        'paused_global_remaining' => 'integer',
        'paused_phase_remaining' => 'integer',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function masterQuestion()
    {
        return $this->belongsTo(MasterQuestion::class);
    }
}
