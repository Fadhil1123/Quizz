<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomQuestion;
use App\Models\Team;

class StateController extends Controller
{
    public function getState($roomId)
    {
        $room = Room::findOrFail($roomId);

        // 1. Ambil Live Leaderboard (Diurutkan dari skor tertinggi)
        $teams = Team::where('room_id', $roomId)
            ->orderBy('current_score', 'desc')
            ->get(['id', 'name', 'current_score']);

        // 2. Ambil Soal yang Sedang Aktif di Room Ini
        $activeRoomQuestion = RoomQuestion::where('room_id', $roomId)
            ->where('status', 'active')
            ->with('masterQuestion')
            ->first();

        $activeQuestionData = null;

        if ($activeRoomQuestion) {
            $mq = $activeRoomQuestion->masterQuestion;
            $activeQuestionData = [
                'room_question_id' => $activeRoomQuestion->id,
                'master_question_id' => $mq->id,
                'question_text' => $mq->question_text,
                'price' => $mq->price,
                // KUNCI JAWABAN (answer_key) SENGADJA TIDAK DIMASUKKAN DEMI KEAMANAN
            ];
        }

        // 3. Response JSON Ringkas untuk Short Polling
        return response()->json([
            'status' => 'success',
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'status' => $room->status,
            ],
            'active_question' => $activeQuestionData,
            'leaderboard' => $teams,
            'timestamp' => now()->timestamp,
        ]);
    }
}