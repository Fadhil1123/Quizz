<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreLog extends Model
{
    protected $fillable = ['room_id', 'team_id', 'question_id', 'action_type', 'amount'];

    public function room() { return $this->belongsTo(Room::class); }
    public function team() { return $this->belongsTo(Team::class); }
    public function question() { return $this->belongsTo(MasterQuestion::class); }
}