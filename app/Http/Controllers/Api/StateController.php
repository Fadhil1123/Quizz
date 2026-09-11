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

        $teams = Team::where('room_id', $roomId)
            ->orderBy('current_score', 'desc')
            ->get(['id', 'name', 'current_score']);

        $activeRoomQuestion = RoomQuestion::where('room_id', $roomId)
            ->where('status', 'active')
            ->with('masterQuestion')
            ->first();

        $activeQuestionData = null;

        if ($activeRoomQuestion) {
            $mq = $activeRoomQuestion->masterQuestion;
            $buyerTeam = $activeRoomQuestion->buyer_team_id ? Team::find($activeRoomQuestion->buyer_team_id) : null;

            $activeQuestionData = [
                'room_question_id' => $activeRoomQuestion->id,
                'master_question_id' => $mq->id,
                'question_text' => $mq->question_text,
                'price' => $mq->price,
                // Pastikan casting boolean ketat (1 / true -> true)
                'is_bought' => (bool) $activeRoomQuestion->is_bought,
                'buyer_team' => $buyerTeam ? $buyerTeam->name : 'Tim Peserta',
            ];
        }

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