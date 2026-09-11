<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'username', 'password', 'role', 'room_id'];
    protected $hidden = ['password', 'remember_token'];

    public function room() { return $this->belongsTo(Room::class); }
}