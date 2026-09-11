<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Room;
use App\Models\Team;
use App\Models\MasterQuestion;
use App\Models\ScoreLog;
use App\Services\ScoreService;

class TestScoreEngine extends Command
{
    protected $signature = 'test:score-engine';
    protected $description = 'Simulasi pengujian logika transaksi poin pada ScoreEngine';

    public function handle(ScoreService $scoreService)
    {
        $this->info("=== MEMULAI SIMULASI SCORE ENGINE ===");

        // Ambil room, tim, dan soal pertama dari database
        $room = Room::first();
        if (!$room) {
            $this->error("Buat room terlebih dahulu dari dashboard Admin!");
            return;
        }

        $teamA = Team::where('room_id', $room->id)->first();
        $teamB = Team::where('room_id', $room->id)->skip(1)->first();
        $question = MasterQuestion::first();

        if (!$teamA || !$teamB || !$question) {
            $this->error("Membutuhkan minimal 2 tim dan 1 soal di database!");
            return;
        }

        $this->line("Data Pengujian: Room '{$room->name}' | Soal '{$question->question_text}' (Harga: {$question->price} Pts)");
        $this->line("Skor Awal {$teamA->name}: {$teamA->current_score} Pts");
        $this->line("Skor Awal {$teamB->name}: {$teamB->current_score} Pts");
        $this->newLine();

        // 1. Simulasikan Beli Soal oleh Tim A
        $this->info("1. Executing: Beli Soal oleh {$teamA->name}");
        $scoreService->buyQuestion($room->id, $teamA->id, $question->id);
        $teamA->refresh();
        $this->line("--> Skor {$teamA->name} setelah beli (-{$question->price}): {$teamA->current_score} Pts");

        // 2. Simulasikan Tim A Jawab BENAR (+2x Price)
        $this->info("2. Executing: {$teamA->name} Jawab BENAR (+2x Price)");
        $scoreService->rewardBuyCorrect($room->id, $teamA->id, $question->id);
        $teamA->refresh();
        $this->line("--> Skor {$teamA->name} setelah BENAR (+".($question->price*2)."): {$teamA->current_score} Pts");

        // 3. Simulasikan Operan BENAR oleh Tim B
        $this->info("3. Executing: Operan BENAR oleh {$teamB->name} (+1x Price)");
        $scoreService->rewardPassCorrect($room->id, $teamB->id, $question->id);
        $teamB->refresh();
        $this->line("--> Skor {$teamB->name} setelah Operan BENAR (+{$question->price}): {$teamB->current_score} Pts");

        $this->newLine();
        $this->info("=== DETAIL MUTASI PADA TABEL SCORE_LOGS ===");
        $logs = ScoreLog::where('room_id', $room->id)->latest()->take(3)->get();
        foreach ($logs as $log) {
            $this->line("- Log #{$log->id} | Team ID: {$log->team_id} | Type: {$log->action_type} | Amount: {$log->amount}");
        }
    }
}