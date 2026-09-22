<?php

namespace App\Http\Controllers\Admin;

use App\Events\RoomStateUpdated;
use App\Http\Controllers\Controller;
use App\Models\MasterQuestion;
use App\Models\Room;
use App\Models\RoomQuestion;
use App\Models\Team;
use App\Services\ScoreService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with('teams')->latest()->get();

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create()
    {
        $masterQuestions = MasterQuestion::all();

        return view('admin.rooms.create', compact('masterQuestions'));
    }

    public function control($id)
    {
        $room = Room::with(['teams', 'roomQuestions.masterQuestion'])->findOrFail($id);

        $activeQuestion = RoomQuestion::where('room_id', $id)
            ->where('status', 'active')
            ->with('masterQuestion')
            ->first();

        return view('admin.rooms.control', compact('room', 'activeQuestion'));
    }

    // 1. Tampilkan Soal (Memicu Global Timer 180s + 2s Buffer)
    public function selectQuestion(Request $request, $roomId)
    {
        $request->validate(['room_question_id' => 'required|exists:room_questions,id']);

        RoomQuestion::where('room_id', $roomId)->where('status', 'active')->update(['status' => 'unused', 'timer_phase' => 'none']);

        $rq = RoomQuestion::findOrFail($request->room_question_id);
        $now = Carbon::now();

        $rq->update([
            'status' => 'active',
            'is_bought' => false,
            'buyer_team_id' => null,
            'timer_phase' => 'papar',
            'is_paused' => false,
            'paused_global_remaining' => null,
            'paused_phase_remaining' => null,
            'global_timer_expires_at' => $now->copy()->addSeconds(182), // 180s + 2s Buffer Latensi
            'timer_expires_at' => $now->copy()->addSeconds(182),
        ]);

        $this->broadcastRoomState($roomId);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Soal aktif! Global Timer (180s) dimulai.']);
        }

        return redirect()->back()->with('success', 'Soal aktif! Global Timer (180s) dimulai.');
    }

    // 2. Transaksi Aksi & Fase Timer
    public function processAction(Request $request, $roomId, ScoreService $scoreService)
    {
        $request->validate([
            'action_type' => 'required|in:BUY,BUY_CORRECT,BUY_WRONG,PASS_CORRECT,PASS_WRONG,CLOSE',
            'team_id' => 'nullable|exists:teams,id',
            'question_id' => 'required|exists:master_questions,id',
            'room_question_id' => 'required|exists:room_questions,id',
        ]);

        $action = $request->action_type;
        $teamId = $request->team_id;
        $questionId = $request->question_id;
        $rqId = $request->room_question_id;

        if ($action === 'BUY' && $teamId) {
            $scoreService->buyQuestion($roomId, $teamId, $questionId);

            // Fase 2: Menjawab (33 detik)
            RoomQuestion::where('id', $rqId)->update([
                'is_bought' => true,
                'buyer_team_id' => $teamId,
                'timer_phase' => 'menjawab',
                'timer_expires_at' => Carbon::now()->addSeconds(33),
            ]);
        } elseif ($action === 'BUY_WRONG') {
            $roomQuestion = RoomQuestion::findOrFail($rqId);
            $now = Carbon::now();
            $globalRemaining = $roomQuestion->is_paused
                ? ($roomQuestion->paused_global_remaining ?? 0)
                : max(0, $now->diffInSeconds($roomQuestion->global_timer_expires_at, false));

            // Fase 3: Operan Rebutan (13 detik). Jika sebelumnya pause,
            // transisi ini juga harus mengaktifkan kembali timer.
            $roomQuestion->update([
                'timer_phase' => 'operan',
                'is_paused' => false,
                'global_timer_expires_at' => $now->copy()->addSeconds($globalRemaining),
                'timer_expires_at' => $now->copy()->addSeconds(13),
                'paused_global_remaining' => null,
                'paused_phase_remaining' => null,
            ]);
        } elseif ($action === 'BUY_CORRECT' && $teamId) {
            $scoreService->rewardBuyCorrect($roomId, $teamId, $questionId);
            RoomQuestion::where('id', $rqId)->update(['status' => 'closed', 'timer_phase' => 'none']);
        } elseif ($action === 'PASS_CORRECT' && $teamId) {
            $scoreService->rewardPassCorrect($roomId, $teamId, $questionId);
            RoomQuestion::where('id', $rqId)->update(['status' => 'closed', 'timer_phase' => 'none']);
        } elseif ($action === 'CLOSE' || $action === 'PASS_WRONG') {
            RoomQuestion::where('id', $rqId)->update(['status' => 'closed', 'timer_phase' => 'none']);
        }

        $this->broadcastRoomState($roomId);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Aksi berhasil dieksekusi!']);
        }

        return redirect()->back()->with('success', 'Aksi berhasil dieksekusi!');
    }

    // 3. Pause Timer
    public function pauseTimer(Request $request, $roomId)
    {
        $request->validate(['room_question_id' => 'required|exists:room_questions,id']);
        $rq = RoomQuestion::findOrFail($request->room_question_id);

        if (! $rq->is_paused) {
            $now = Carbon::now();
            $globalRem = $rq->global_timer_expires_at ? max(0, $now->diffInSeconds($rq->global_timer_expires_at, false)) : 0;
            $phaseRem = $rq->timer_expires_at ? max(0, $now->diffInSeconds($rq->timer_expires_at, false)) : 0;

            $rq->update([
                'is_paused' => true,
                'paused_global_remaining' => $globalRem,
                'paused_phase_remaining' => $phaseRem,
            ]);
        }

        $this->broadcastRoomState($roomId);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Timer berhasil di-PAUSE.']);
        }

        return redirect()->back()->with('success', 'Timer berhasil di-PAUSE ⏸️');
    }

    // 4. Resume Timer
    public function resumeTimer(Request $request, $roomId)
    {
        $request->validate(['room_question_id' => 'required|exists:room_questions,id']);
        $rq = RoomQuestion::findOrFail($request->room_question_id);

        if ($rq->is_paused) {
            $now = Carbon::now();
            // Tambahkan +2 detik buffer saat di-resume
            $newGlobalExpires = $now->copy()->addSeconds(($rq->paused_global_remaining ?? 0) + 2);
            $newPhaseExpires = $now->copy()->addSeconds(($rq->paused_phase_remaining ?? 0) + 2);

            $rq->update([
                'is_paused' => false,
                'global_timer_expires_at' => $newGlobalExpires,
                'timer_expires_at' => $newPhaseExpires,
                'paused_global_remaining' => null,
                'paused_phase_remaining' => null,
            ]);
        }

        $this->broadcastRoomState($roomId);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Timer dilanjutkan.']);
        }

        return redirect()->back()->with('success', 'Timer dilanjutkan ▶️');
    }

    // 5. Reset Timer Darurat (Reset Total ke 180s + 2s Buffer)
    public function resetQuestionTimer(Request $request, $roomId)
    {
        $request->validate(['room_question_id' => 'required|exists:room_questions,id']);
        $rq = RoomQuestion::findOrFail($request->room_question_id);
        $now = Carbon::now();

        $rq->update([
            'is_bought' => false,
            'buyer_team_id' => null,
            'timer_phase' => 'papar',
            'is_paused' => false,
            'paused_global_remaining' => null,
            'paused_phase_remaining' => null,
            'global_timer_expires_at' => $now->copy()->addSeconds(182),
            'timer_expires_at' => $now->copy()->addSeconds(182),
        ]);

        $this->broadcastRoomState($roomId);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Timer soal di-reset kembali ke 180s.']);
        }

        return redirect()->back()->with('success', 'Timer soal di-reset kembali ke 180s!');
    }

    // 6. Selesaikan Kuis
    public function finishRoom(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $room->update(['status' => 'finished']);

        RoomQuestion::where('room_id', $id)
            ->where('status', 'active')
            ->update(['status' => 'closed', 'timer_phase' => 'none']);

        $this->broadcastRoomState($id);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Kuis resmi SELESAI! 🏆']);
        }

        return redirect()->back()->with('success', 'Kuis resmi SELESAI! 🏆');
    }

    // 7. Simpan Room Baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'initial_score' => 'required|integer|min:0',
            'teams' => 'required|array|min:2',
            'teams.*' => 'required|string|max:100',
            'questions' => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $room = Room::create([
                'name' => $request->name,
                'status' => 'waiting',
            ]);

            foreach ($request->teams as $teamName) {
                if (! empty(trim($teamName))) {
                    Team::create([
                        'room_id' => $room->id,
                        'name' => $teamName,
                        'current_score' => $request->initial_score,
                    ]);
                }
            }

            foreach ($request->questions as $questionId) {
                RoomQuestion::create([
                    'room_id' => $room->id,
                    'master_question_id' => $questionId,
                    'status' => 'unused',
                ]);
            }
        });

        return redirect()->route('admin.rooms.index')->with('success', 'Ruangan kuis berhasil dibuat!');
    }

    /**
     * Memasukkan state kuis terbaru ke queue broadcast.
     */
    private function broadcastRoomState($roomId): void
    {
        broadcast(new RoomStateUpdated($roomId));
    }
}
