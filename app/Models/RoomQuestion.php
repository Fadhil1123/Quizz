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
        'timer_expires_at'
    ];

    protected $casts = [
        'timer_expires_at' => 'datetime',
    ];

    public function room() { return $this->belongsTo(Room::class); }
    public function masterQuestion() { return $this->belongsTo(MasterQuestion::class); }
}