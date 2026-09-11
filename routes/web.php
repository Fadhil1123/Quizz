<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\MasterQuestionController;
use App\Http\Controllers\Admin\RoomController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Group Route khusus Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/rooms', function () {
        return 'Halaman Dashboard Admin Rooms (Lanjut di Issue #3)';
    });
});

// Group Route khusus Operator
Route::middleware(['auth', 'role:operator'])->prefix('operator')->group(function () {
    Route::get('/select-room', function () {
        return 'Halaman Select Room Operator (Lanjut di Issue #6)';
    });
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Room Management
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');

    // Master Question Management
    Route::get('/master-questions', [MasterQuestionController::class, 'index'])->name('master-questions.index');
    Route::post('/master-questions', [MasterQuestionController::class, 'store'])->name('master-questions.store');
    Route::delete('/master-questions/{id}', [MasterQuestionController::class, 'destroy'])->name('master-questions.destroy');
});