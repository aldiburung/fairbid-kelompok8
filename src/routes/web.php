<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'beranda')->name('home');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
