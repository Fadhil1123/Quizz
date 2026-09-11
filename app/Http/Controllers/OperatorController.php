<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    // Halaman Pilih Room untuk Operator
    public function selectRoom()
    {
        $rooms = Room::whereIn('status', ['waiting', 'ongoing'])->get();
        return view('operator.select-room', compact('rooms'));
    }

    // Halaman Stage Display / Proyektor
    public function stage($id)
    {
        $room = Room::findOrFail($id);
        return view('operator.stage', compact('room'));
    }
}