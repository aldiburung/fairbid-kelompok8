<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $timestamp = $now->toDateTimeString();
        $accounts = [
            'admin@fairbid.test' => [
                'name' => 'Admin FairBid',
                'phone' => '081200000001',
                'role' => 'ADMIN',
                'nik' => '3174010101900001',
                'birth_date' => '1990-01-01',
            ],
            'penjual@fairbid.test' => [
                'name' => 'Penjual Demo',
                'phone' => '081200000002',
                'role' => 'USER',
                'nik' => '3174010202900002',
                'birth_date' => '1990-02-02',
            ],
            'peserta1@fairbid.test' => [
                'name' => 'Peserta Demo Satu',
                'phone' => '081200000003',
                'role' => 'USER',
                'nik' => '3174010303900003',
                'birth_date' => '1990-03-03',
            ],
            'peserta2@fairbid.test' => [
                'name' => 'Peserta Demo Dua',
                'phone' => '081200000004',
                'role' => 'USER',
                'nik' => '3174010404900004',
                'birth_date' => '1990-04-04',
            ],
        ];

        foreach ($accounts as $email => $account) {
            DB::table('user')->updateOrInsert(
                ['email' => $email],
                [
                    'password_hash' => Hash::make('password123'),
                    'no_hp' => $account['phone'],
                    'tipe_akun' => 'PERSONAL',
                    'peran' => $account['role'],
                    'status_verifikasi' => 'DISETUJUI',
                    'status_akun' => 'AKTIF',
                    'created_at' => $timestamp,
                ],
            );

            $userId = DB::table('user')->where('email', $email)->value('user_id');
            DB::table('personal_profile')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'nama_lengkap' => $account['name'],
                    'nik' => $account['nik'],
                    'tgl_lahir' => $account['birth_date'],
                    'alamat' => 'Alamat demo FairBid',
                ],
            );
        }

        $sellerId = DB::table('user')->where('email', 'penjual@fairbid.test')->value('user_id');
        $auctions = [
            [
                'title' => 'Laptop Ultrabook Demo FairBid',
                'category' => 'Elektronik',
                'description' => 'Laptop demo untuk mencoba proses lelang FairBid.',
                'price' => 1000000,
                'increment' => 100000,
                'days' => 5,
            ],
            [
                'title' => 'Tas Kulit Demo FairBid',
                'category' => 'Fashion',
                'description' => 'Tas kulit demo dengan kondisi terawat.',
                'price' => 500000,
                'increment' => 50000,
                'days' => 6,
            ],
            [
                'title' => 'Koleksi Perangko Demo FairBid',
                'category' => 'Koleksi dan Antik',
                'description' => 'Koleksi perangko demo untuk penggemar filateli.',
                'price' => 250000,
                'increment' => 25000,
                'days' => 7,
            ],
            [
                'title' => 'Aksesori Otomotif Demo FairBid',
                'category' => 'Otomotif',
                'description' => 'Aksesori otomotif demo untuk pengguna FairBid.',
                'price' => 750000,
                'increment' => 75000,
                'days' => 8,
            ],
        ];

        $auctionIds = [];
        foreach ($auctions as $auction) {
            $categoryId = Category::query()
                ->where('nama_kategori', $auction['category'])
                ->firstOrFail()
                ->category_id;
            $startTime = $now->copy()->subMinute();

            DB::table('auction')->updateOrInsert(
                ['judul' => $auction['title']],
                [
                    'seller_id' => $sellerId,
                    'category_id' => $categoryId,
                    'deskripsi' => $auction['description'],
                    'model_transaksi' => 'C2C',
                    'harga_awal' => $auction['price'],
                    'harga_limit' => null,
                    'kelipatan_bid' => $auction['increment'],
                    'nilai_ujl' => round($auction['price'] * 0.10, 2),
                    'start_time' => $startTime->toDateTimeString(),
                    'end_time' => $now->copy()->addDays($auction['days'])->toDateTimeString(),
                    'status' => 'AKTIF',
                    'created_at' => $timestamp,
                ],
            );

            $auctionIds[$auction['title']] = DB::table('auction')
                ->where('judul', $auction['title'])
                ->value('auction_id');
        }

        $bidAuction = $auctions[0];
        $bidAuctionId = $auctionIds[$bidAuction['title']];
        $participants = [
            ['email' => 'peserta1@fairbid.test', 'label' => 'peserta1'],
            ['email' => 'peserta2@fairbid.test', 'label' => 'peserta2'],
        ];
        $depositIds = [];

        foreach ($participants as $participant) {
            $participantId = DB::table('user')
                ->where('email', $participant['email'])
                ->value('user_id');

            DB::table('auction_deposit')->updateOrInsert(
                [
                    'auction_id' => $bidAuctionId,
                    'user_id' => $participantId,
                ],
                [
                    'nominal_ujl' => round($bidAuction['price'] * 0.10, 2),
                    'status' => 'DITAHAN',
                    'terms_accepted_at' => $timestamp,
                    'paid_at' => $timestamp,
                    'settled_at' => null,
                ],
            );

            $depositId = DB::table('auction_deposit')
                ->where('auction_id', $bidAuctionId)
                ->where('user_id', $participantId)
                ->value('deposit_id');
            $depositIds[$participant['label']] = $depositId;
            $paymentKey = 'demo-ujl-' . $participant['label'] . '-laptop';

            DB::table('payment')->updateOrInsert(
                ['idempotency_key' => $paymentKey],
                [
                    'user_id' => $participantId,
                    'deposit_id' => $depositId,
                    'transaction_id' => null,
                    'jenis' => 'UJL',
                    'amount' => round($bidAuction['price'] * 0.10, 2),
                    'gateway_ref' => 'DEMO-UJL-' . strtoupper($participant['label']) . '-LAPTOP',
                    'status' => 'BERHASIL',
                    'webhook_at' => $timestamp,
                    'created_at' => $timestamp,
                ],
            );
        }

        $bidSequence = [
            ['bidder' => 'peserta1', 'amount' => $bidAuction['price'] + $bidAuction['increment']],
            ['bidder' => 'peserta2', 'amount' => $bidAuction['price'] + ($bidAuction['increment'] * 2)],
            ['bidder' => 'peserta1', 'amount' => $bidAuction['price'] + ($bidAuction['increment'] * 3)],
        ];

        foreach ($bidSequence as $index => $bid) {
            DB::table('bid')->updateOrInsert(
                [
                    'auction_id' => $bidAuctionId,
                    'sequence_no' => $index + 1,
                ],
                [
                    'bidder_id' => DB::table('user')
                        ->where('email', $bid['bidder'] . '@fairbid.test')
                        ->value('user_id'),
                    'deposit_id' => $depositIds[$bid['bidder']],
                    'amount' => $bid['amount'],
                    'server_timestamp' => $now->copy()->addSeconds($index)->toDateTimeString(),
                    'status' => 'VALID',
                    'is_flagged' => false,
                    'extended_auction' => false,
                ],
            );
        }
    }
}
