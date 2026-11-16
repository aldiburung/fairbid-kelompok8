<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminAuctionController;
use App\Http\Controllers\AdminVerificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\BidController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Models\Auction;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $auctions = Auction::with(['auctionMedia' => function ($query) {
        $query->where('tipe', 'FOTO')->orderBy('urutan')->orderBy('media_id');
    }])
        ->withMax(['bids as harga_tertinggi' => function ($query) {
            $query->where('status', 'VALID');
        }], 'amount')
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
    Route::get('/lupa-sandi', [PasswordResetController::class, 'showRequest'])->name('password.request');
    Route::post('/lupa-sandi', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-sandi/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-sandi', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/akun/profil', [ProfileController::class, 'show'])->name('account.profile');
    Route::post('/akun/profil', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::get('/akun/verifikasi', [ProfileController::class, 'resubmitForm'])->name('account.verification.resubmit.form');
    Route::post('/akun/verifikasi', [ProfileController::class, 'resubmit'])->name('account.verification.resubmit');
    Route::get('/akun/lelang-saya', [AccountController::class, 'lelangSaya'])->name('account.lelang-saya');
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/{notification}/baca', [NotificationController::class, 'markRead'])
        ->name('notifications.read');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::get('/auctions/create', [AuctionController::class, 'create'])->name('auctions.create');
    Route::post('/auctions', [AuctionController::class, 'store'])->name('auctions.store');
    Route::post('/auctions/{auction}/batal', [AuctionController::class, 'cancel'])->name('auctions.cancel');
    Route::post('/auctions/{auction}/bids', [BidController::class, 'store'])->name('bids.store');
    Route::post('/auctions/{auction}/join', [DepositController::class, 'join'])->name('deposits.join');
    Route::get('/deposits/{deposit}/bayar', [DepositController::class, 'pay'])->name('deposits.pay');
    Route::post('/sandbox/payments/{payment}/simulasi', [DepositController::class, 'simulasi'])
        ->name('sandbox.payments.simulasi');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transactions/{transaction}/bayar', [TransactionController::class, 'pay'])->name('transactions.pay');
    Route::post('/sandbox/transactions/{transaction}/simulasi', [TransactionController::class, 'simulasi'])
        ->name('sandbox.transactions.simulasi');
    Route::post('/transactions/{transaction}/serah-terima', [TransactionController::class, 'confirmHandover'])
        ->name('transactions.handover');
    Route::post('/transactions/{transaction}/diterima', [TransactionController::class, 'confirmReceived'])
        ->name('transactions.received');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/lelang', [AdminAuctionController::class, 'index'])->name('lelang.index');
    Route::post('/lelang/{auction}/turunkan', [AdminAuctionController::class, 'turunkan'])->name('lelang.turunkan');
    Route::get('/verifikasi', [AdminVerificationController::class, 'index'])
        ->name('verifikasi.index');
    Route::post('/verifikasi/{verification}/setujui', [AdminVerificationController::class, 'approve'])
        ->name('verifikasi.approve');
    Route::post('/verifikasi/{verification}/tolak', [AdminVerificationController::class, 'reject'])
        ->name('verifikasi.reject');
});

Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
Route::get('/auctions/{auction}/state', [AuctionController::class, 'state'])->name('auctions.state');
Route::get('/auctions/{auction}', [AuctionController::class, 'show'])->name('auctions.show');
