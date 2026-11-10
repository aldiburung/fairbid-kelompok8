<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\DepositController;
use App\Models\Auction;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $auctions = Auction::with(['auctionMedia' => function ($query) {
        $query->where('tipe', 'FOTO')->orderBy('urutan')->orderBy('media_id');
    }])
        ->where('status', 'AKTIF')
        ->orderByDesc('created_at')
        ->limit(6)
        ->get();

    return view('beranda', compact('auctions'));
})->name('home');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/auctions/create', [AuctionController::class, 'create'])->name('auctions.create');
    Route::post('/auctions', [AuctionController::class, 'store'])->name('auctions.store');
    Route::post('/auctions/{auction}/join', [DepositController::class, 'join'])->name('deposits.join');
    Route::get('/deposits/{deposit}/bayar', [DepositController::class, 'pay'])->name('deposits.pay');
    Route::post('/sandbox/payments/{payment}/simulasi', [DepositController::class, 'simulasi'])
        ->name('sandbox.payments.simulasi');
});

Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
Route::get('/auctions/{auction}/state', [AuctionController::class, 'state'])->name('auctions.state');
Route::get('/auctions/{auction}', [AuctionController::class, 'show'])->name('auctions.show');
