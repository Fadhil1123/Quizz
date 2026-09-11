<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomQuestion extends Model
{
    protected $fillable = ['room_id', 'master_question_id', 'status'];

    public function room() { return $this->belongsTo(Room::class); }
    public function masterQuestion() { return $this->belongsTo(MasterQuestion::class); }
}