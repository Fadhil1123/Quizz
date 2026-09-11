<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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