<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['name', 'status'];

    public function users() { return $this->hasMany(User::class); }
    public function teams() { return $this->hasMany(Team::class); }
    public function roomQuestions() { return $this->hasMany(RoomQuestion::class); }
    public function scoreLogs() { return $this->hasMany(ScoreLog::class); }
}