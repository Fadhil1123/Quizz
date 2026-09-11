<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterQuestion;
use Illuminate\Http\Request;

class MasterQuestionController extends Controller
{
    public function index()
    {
        $questions = MasterQuestion::latest()->get();
        return view('admin.master-questions.index', compact('questions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'question_text' => 'required|string',
            'price' => 'required|integer|min:100',
            'answer_key' => 'required|string',
        ]);

        MasterQuestion::create($request->all());

        return redirect()->back()->with('success', 'Soal berhasil ditambahkan ke Master Bank!');
    }

    public function destroy($id)
    {
        MasterQuestion::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Soal berhasil dihapus!');
    }
}