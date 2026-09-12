<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomQuestion;
use App\Models\Team;
use Carbon\Carbon;

class StateController extends Controller
{
    public function getState($roomId)
    {
        $room = Room::findOrFail($roomId);

        // 1. Ambil seluruh tim terurut dari skor tertinggi (untuk Winner Overlay & Leaderboard)
        $teams = Team::where('room_id', $roomId)
            ->orderBy('current_score', 'desc')
            ->get(['id', 'name', 'current_score']);

        // JIKA STATUS ROOM FINISHED: Langsung kembalikan status finished dan leaderboard pemenang!
        if ($room->status === 'finished') {
            return response()->json([
                'status' => 'success',
                'room' => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'status' => 'finished',
                ],
                'active_question' => null,
                'leaderboard' => $teams,
                'timestamp' => now()->timestamp,
            ]);
        }

        // 2. Jika room masih aktif/waiting, cari soal yang sedang aktif
        $activeRoomQuestion = RoomQuestion::where('room_id', $roomId)
            ->where('status', 'active')
            ->with('masterQuestion')
            ->first();

        $activeQuestionData = null;

        if ($activeRoomQuestion) {
            $mq = $activeRoomQuestion->masterQuestion;
            $buyerTeam = $activeRoomQuestion->buyer_team_id ? Team::find($activeRoomQuestion->buyer_team_id) : null;

            // Hitung sisa detik
            $remainingSeconds = 0;
            if ($activeRoomQuestion->timer_expires_at) {
                $remainingSeconds = max(0, Carbon::now()->diffInSeconds($activeRoomQuestion->timer_expires_at, false));
            }

            // AUTO-TRANSITION & AUTO-CLOSE
            if ($remainingSeconds <= 0 && $activeRoomQuestion->timer_phase !== 'none') {
                if ($activeRoomQuestion->timer_phase === 'menjawab') {
                    // Waktu pembeli habis -> pindah ke operan (10s)
                    $activeRoomQuestion->update([
                        'timer_phase' => 'operan',
                        'timer_expires_at' => Carbon::now()->addSeconds(10),
                    ]);
                    $remainingSeconds = 10;
                } elseif ($activeRoomQuestion->timer_phase === 'operan' || $activeRoomQuestion->timer_phase === 'papar') {
                    // Waktu operan/papar habis -> tutup soal
                    $activeRoomQuestion->update([
                        'status' => 'closed',
                        'timer_phase' => 'none',
                    ]);
                    $activeRoomQuestion = null;
                }
            }

            if ($activeRoomQuestion && $activeRoomQuestion->status === 'active') {
                $activeQuestionData = [
                    'room_question_id' => $activeRoomQuestion->id,
                    'master_question_id' => $mq->id,
                    'question_text' => $mq->question_text,
                    'price' => $mq->price,
                    'is_bought' => (bool) $activeRoomQuestion->is_bought,
                    'buyer_team' => $buyerTeam ? $buyerTeam->name : null,
                    'timer_phase' => $activeRoomQuestion->timer_phase,
                    'remaining_seconds' => (int) $remainingSeconds,
                ];
            }
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