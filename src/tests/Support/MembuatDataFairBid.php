<?php

namespace Tests\Support;

use App\Models\Auction;
use App\Models\AuctionDeposit;
use App\Models\Bid;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait MembuatDataFairBid
{
    protected function buatUser(array $override = []): User
    {
        $password = $override['password'] ?? $override['password_hash'] ?? 'password123';
        unset($override['password'], $override['password_hash']);

        return User::query()->create(array_merge([
            'email' => Str::uuid() . '@fairbid.test',
            'no_hp' => '081234567890',
            'tipe_akun' => 'PERSONAL',
            'peran' => 'USER',
            'status_verifikasi' => 'DISETUJUI',
            'status_akun' => 'AKTIF',
        ], $override, [
            'password_hash' => Hash::make((string) $password),
        ]));
    }

    protected function buatKategori(array $override = []): Category
    {
        return Category::query()->create(array_merge([
            'nama_kategori' => 'Kategori ' . Str::uuid(),
            'izinkan_perorangan' => true,
        ], $override));
    }

    protected function buatLelang(User $penjual, array $override = []): Auction
    {
        $categoryId = $override['category_id'] ?? $this->buatKategori()->category_id;
        unset($override['category_id']);

        return Auction::query()->create(array_merge([
            'seller_id' => $penjual->user_id,
            'category_id' => $categoryId,
            'judul' => 'Lelang ' . Str::uuid(),
            'deskripsi' => 'Deskripsi lelang untuk pengujian.',
            'model_transaksi' => 'C2C',
            'harga_awal' => 100000,
            'harga_limit' => null,
            'kelipatan_bid' => 10000,
            'nilai_ujl' => 10000,
            'start_time' => now()->subMinute(),
            'end_time' => now()->addDay(),
            'status' => 'AKTIF',
        ], $override, [
            'seller_id' => $penjual->user_id,
            'category_id' => $categoryId,
        ]));
    }

    protected function buatDepositDitahan(Auction $auction, User $user): AuctionDeposit
    {
        return AuctionDeposit::query()->create([
            'auction_id' => $auction->auction_id,
            'user_id' => $user->user_id,
            'nominal_ujl' => $auction->nilai_ujl,
            'status' => 'DITAHAN',
            'terms_accepted_at' => now(),
            'paid_at' => now(),
        ]);
    }

    protected function buatBid(
        Auction $auction,
        User $penawar,
        AuctionDeposit $deposit,
        array $override = [],
    ): Bid {
        $lastSequence = (int) Bid::query()
            ->where('auction_id', $auction->auction_id)
            ->max('sequence_no');
        $highestAmount = Bid::query()
            ->where('auction_id', $auction->auction_id)
            ->where('status', 'VALID')
            ->max('amount');
        $amount = $highestAmount === null
            ? (float) $auction->harga_awal
            : (float) $highestAmount + (float) $auction->kelipatan_bid;

        return Bid::query()->create(array_merge([
            'auction_id' => $auction->auction_id,
            'bidder_id' => $penawar->user_id,
            'deposit_id' => $deposit->deposit_id,
            'amount' => $amount,
            'sequence_no' => $lastSequence + 1,
            'server_timestamp' => now(),
            'status' => 'VALID',
            'extended_auction' => false,
        ], $override));
    }
}
