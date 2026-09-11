<?php

namespace App\Services;

use App\Models\Team;
use App\Models\MasterQuestion;
use App\Models\ScoreLog;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    /**
     * 1. Transaksi Beli Soal: Potong poin sebesar harga soal (-1x Price)
     */
    public function buyQuestion(int $roomId, int $teamId, int $questionId): void
    {
        DB::transaction(function () use ($roomId, $teamId, $questionId) {
            $question = MasterQuestion::findOrFail($questionId);
            $team = Team::where('room_id', $roomId)->findOrFail($teamId);

            $deduction = -$question->price;
            $team->increment('current_score', $deduction);

            ScoreLog::create([
                'room_id' => $roomId,
                'team_id' => $teamId,
                'question_id' => $questionId,
                'action_type' => 'BUY_DEDUCTION',
                'amount' => $deduction,
            ]);
        });
    }

    /**
     * 2. Pembeli Utama BENAR: Tambah imbalan (+2x Price) -> Net untung = +1x Price
     */
    public function rewardBuyCorrect(int $roomId, int $teamId, int $questionId): void
    {
        DB::transaction(function () use ($roomId, $teamId, $questionId) {
            $question = MasterQuestion::findOrFail($questionId);
            $team = Team::where('room_id', $roomId)->findOrFail($teamId);

            $reward = $question->price * 2;
            $team->increment('current_score', $reward);

            ScoreLog::create([
                'room_id' => $roomId,
                'team_id' => $teamId,
                'question_id' => $questionId,
                'action_type' => 'BUY_CORRECT',
                'amount' => $reward,
            ]);
        });
    }

    /**
     * 3. Operan BENAR: Tambah imbalan (+1x Price) tanpa pemotongan poin awal
     */
    public function rewardPassCorrect(int $roomId, int $teamId, int $questionId): void
    {
        DB::transaction(function () use ($roomId, $teamId, $questionId) {
            $question = MasterQuestion::findOrFail($questionId);
            $team = Team::where('room_id', $roomId)->findOrFail($teamId);

            $reward = $question->price;
            $team->increment('current_score', $reward);

            ScoreLog::create([
                'room_id' => $roomId,
                'team_id' => $teamId,
                'question_id' => $questionId,
                'action_type' => 'PASS_CORRECT',
                'amount' => $reward,
            ]);
        });
    }
}