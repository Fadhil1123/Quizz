<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['room_id', 'name', 'current_score'];

    public function room() { return $this->belongsTo(Room::class); }
    public function scoreLogs() { return $this->hasMany(ScoreLog::class); }
}