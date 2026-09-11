<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomQuestion extends Model
{
    // INI YANG SERING TERLEWAT: is_bought dan buyer_team_id WAJIB DITULIS DI SINI!
    protected $fillable = [
        'room_id', 
        'master_question_id', 
        'status', 
        'is_bought',        // <- Pastikan ini ada
        'buyer_team_id'     // <- Pastikan ini ada
    ];

    public function room() { return $this->belongsTo(Room::class); }
    public function masterQuestion() { return $this->belongsTo(MasterQuestion::class); }
}