<?php

use App\Http\Controllers\Admin\MasterQuestionController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OperatorController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Group Route Khusus Admin (Terpusat dengan Prefix 'admin' & Name 'admin.')
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Room Management
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');

    // Live Control Center & Quiz Execution
    Route::get('/rooms/{id}/control', [RoomController::class, 'control'])->name('rooms.control');
    Route::post('/rooms/{id}/select-question', [RoomController::class, 'selectQuestion'])->name('rooms.select-question');
    Route::post('/rooms/{id}/action', [RoomController::class, 'processAction'])->name('rooms.process-action');
    Route::post('/rooms/{id}/finish', [RoomController::class, 'finishRoom'])->name('rooms.finish');

    // Master Question Management
    Route::get('/master-questions', [MasterQuestionController::class, 'index'])->name('master-questions.index');
    Route::post('/master-questions', [MasterQuestionController::class, 'store'])->name('master-questions.store');
    Route::delete('/master-questions/{id}', [MasterQuestionController::class, 'destroy'])->name('master-questions.destroy');

    // Route Pengaturan Waktu
    Route::post('/rooms/{id}/reset-timer', [RoomController::class, 'resetQuestionTimer'])->name('rooms.reset-timer');
    Route::post('/rooms/{id}/pause-timer', [RoomController::class, 'pauseTimer'])->name('rooms.pause-timer');
    Route::post('/rooms/{id}/resume-timer', [RoomController::class, 'resumeTimer'])->name('rooms.resume-timer');
});

// Group Route Khusus Operator
Route::middleware(['auth', 'role:operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/select-room', [OperatorController::class, 'selectRoom'])->name('select-room');
    Route::get('/stage/{id}', [OperatorController::class, 'stage'])->name('stage');
});
