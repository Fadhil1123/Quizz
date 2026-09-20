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

        // 1. Ambil seluruh tim terurut dari skor tertinggi
        $teams = Team::where('room_id', $roomId)
            ->orderBy('current_score', 'desc')
            ->get(['id', 'name', 'current_score']);

        // JIKA STATUS ROOM FINISHED
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

        // 2. Ambil soal yang sedang aktif
        $activeRoomQuestion = RoomQuestion::where('room_id', $roomId)
            ->where('status', 'active')
            ->with('masterQuestion')
            ->first();

        $activeQuestionData = null;

        if ($activeRoomQuestion) {
            $mq = $activeRoomQuestion->masterQuestion;
            $buyerTeam = $activeRoomQuestion->buyer_team_id ? Team::find($activeRoomQuestion->buyer_team_id) : null;

            $globalRemaining = 0;
            $phaseRemaining = 0;

            // LOGIKA TIMER: CEK STATUS PAUSED VS RUNNING
            if ($activeRoomQuestion->is_paused) {
                // Saat Paused, gunakan nilai detik yang dibekukan
                $globalRemaining = max(0, $activeRoomQuestion->paused_global_remaining ?? 0);
                $phaseRemaining = max(0, $activeRoomQuestion->paused_phase_remaining ?? 0);
            } else {
                // Saat Running, kalkulasi selisih Carbon
                if ($activeRoomQuestion->global_timer_expires_at) {
                    $globalRemaining = max(0, Carbon::now()->diffInSeconds($activeRoomQuestion->global_timer_expires_at, false));
                }
                if ($activeRoomQuestion->timer_expires_at) {
                    $phaseRemaining = max(0, Carbon::now()->diffInSeconds($activeRoomQuestion->timer_expires_at, false));
                }

                // ATURAN HANGUS UTAMA: Jika Global Timer (3 Menit) habis -> Soal otomatis CLOSED
                if ($globalRemaining <= 0 && $activeRoomQuestion->global_timer_expires_at) {
                    $activeRoomQuestion->update([
                        'status' => 'closed',
                        'timer_phase' => 'none',
                    ]);
                    $activeRoomQuestion = null;
                }
                // AUTO-TRANSITION SUB-FASE (Jika Global Timer masih ada tapi Timer Fase Habis)
                elseif ($phaseRemaining <= 0 && $activeRoomQuestion->timer_phase !== 'none') {
                    if ($activeRoomQuestion->timer_phase === 'menjawab') {
                        // Waktu pembeli habis -> Pindah ke Operan (10s + 2s buffer latensi)
                        $activeRoomQuestion->update([
                            'timer_phase' => 'operan',
                            'timer_expires_at' => Carbon::now()->addSeconds(12),
                        ]);
                        $phaseRemaining = 12;
                    } elseif ($activeRoomQuestion->timer_phase === 'operan' || $activeRoomQuestion->timer_phase === 'papar') {
                        // Waktu operan/papar habis -> Tutup soal
                        $activeRoomQuestion->update([
                            'status' => 'closed',
                            'timer_phase' => 'none',
                        ]);
                        $activeRoomQuestion = null;
                    }
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
                    'is_paused' => (bool) $activeRoomQuestion->is_paused,
                    'global_remaining_seconds' => (int) $globalRemaining,
                    'phase_remaining_seconds' => (int) $phaseRemaining,
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
