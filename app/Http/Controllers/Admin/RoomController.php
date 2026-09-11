<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\MasterQuestion;
use App\Models\Team;
use App\Models\RoomQuestion;
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