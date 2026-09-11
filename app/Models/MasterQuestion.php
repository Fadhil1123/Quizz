<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterQuestion extends Model
{
    protected $fillable = ['question_text', 'price', 'answer_key'];

    public function roomQuestions() { return $this->hasMany(RoomQuestion::class); }
}