<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\MasterQuestion;
use App\Models\Team;
use App\Models\RoomQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ScoreService;

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
        
        // Soal yang sedang aktif
        $activeQuestion = RoomQuestion::where('room_id', $id)
            ->where('status', 'active')
            ->with('masterQuestion')
            ->first();

        return view('admin.rooms.control', compact('room', 'activeQuestion'));
    }

    // Method untuk Memilih Soal menjadi AKTIF
    public function selectQuestion(Request $request, $roomId)
    {
        $request->validate(['room_question_id' => 'required|exists:room_questions,id']);

        RoomQuestion::where('room_id', $roomId)->where('status', 'active')->update(['status' => 'unused']);

        $rq = RoomQuestion::findOrFail($request->room_question_id);
        $rq->update([
            'status' => 'active',
            'is_bought' => false,
            'buyer_team_id' => null,
        ]);

        return redirect()->back()->with('success', 'Harga soal berhasil ditampilkan di panggung!');
    }

    // Method untuk Mengeksekusi Transaksi Poin
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
            // 1. Eksekusi pemotongan poin
            $scoreService->buyQuestion($roomId, $teamId, $questionId);

            // 2. Update status terbeli langsung pada primary key room_questions
            RoomQuestion::where('id', $rqId)->update([
                'is_bought' => true,
                'buyer_team_id' => $teamId,
            ]);
        } elseif ($action === 'BUY_CORRECT' && $teamId) {
            $scoreService->rewardBuyCorrect($roomId, $teamId, $questionId);
            RoomQuestion::where('id', $rqId)->update(['status' => 'closed']);
        } elseif ($action === 'PASS_CORRECT' && $teamId) {
            $scoreService->rewardPassCorrect($roomId, $teamId, $questionId);
            RoomQuestion::where('id', $rqId)->update(['status' => 'closed']);
        } elseif ($action === 'CLOSE') {
            RoomQuestion::where('id', $rqId)->update(['status' => 'closed']);
        }

        return redirect()->back()->with('success', 'Aksi berhasil dieksekusi!');
    }
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
            // 1. Buat Room
            $room = Room::create([
                'name' => $request->name,
                'status' => 'waiting',
            ]);

            // 2. Buat Tim
            foreach ($request->teams as $teamName) {
                if (!empty(trim($teamName))) {
                    Team::create([
                        'room_id' => $room->id,
                        'name' => $teamName,
                        'current_score' => $request->initial_score,
                    ]);
                }
            }

            // 3. Assign Soal dari Master Bank
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
}