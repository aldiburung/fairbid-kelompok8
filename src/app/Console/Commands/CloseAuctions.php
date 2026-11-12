<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Transaction;
use App\Services\NotificationService;
use App\Services\SettlementService;
use Illuminate\Console\Command;

class CloseAuctions extends Command
{
    protected $signature = 'auctions:close';

    protected $description = 'Memproses penutupan lelang dan settlement transaksi.';

    public function handle(
        SettlementService $settlementService,
        NotificationService $notificationService,
    ): int
    {
        $now = now();
        $startingAuctions = Auction::query()
            ->where('status', 'TERJADWAL')
            ->where('start_time', '<=', $now)
            ->get(['auction_id', 'seller_id', 'judul']);

        $startedAuctions = Auction::query()
            ->where('status', 'TERJADWAL')
            ->where('start_time', '<=', $now)
            ->update(['status' => 'AKTIF']);

        $activatedAuctionIds = $startingAuctions->pluck('auction_id');
        if ($activatedAuctionIds->isNotEmpty()) {
            Auction::query()
                ->whereIn('auction_id', $activatedAuctionIds)
                ->where('status', 'AKTIF')
                ->get(['auction_id'])
                ->each(function (Auction $auction) use ($startingAuctions, $notificationService): void {
                    $startedAuction = $startingAuctions->firstWhere('auction_id', $auction->auction_id);
                    $notificationService->sendOnce(
                        (int) $startedAuction->seller_id,
                        'LELANG_MULAI',
                        'Lelang ' . $startedAuction->judul . ' telah dimulai.',
                        (int) $startedAuction->auction_id,
                    );
                });
        }

        $closedAuctions = 0;
        Auction::query()
            ->where('status', 'AKTIF')
            ->where('end_time', '<=', now())
            ->orderBy('auction_id')
            ->get()
            ->each(function (Auction $auction) use ($settlementService, &$closedAuctions): void {
                $settlementService->closeAuction($auction);
                $closedAuctions++;
            });

        $expiredTransactions = 0;
        Transaction::query()
            ->where('status', 'MENUNGGU_PELUNASAN')
            ->where('batas_pelunasan', '<=', now())
            ->orderBy('transaction_id')
            ->get()
            ->each(function (Transaction $transaction) use ($settlementService, &$expiredTransactions): void {
                $settlementService->expireUnpaid($transaction);
                $expiredTransactions++;
            });

        $reminderNow = now();
        Transaction::query()
            ->with('auction')
            ->where('status', 'MENUNGGU_PELUNASAN')
            ->where('batas_pelunasan', '>', $reminderNow)
            ->where('batas_pelunasan', '<=', $reminderNow->copy()->addHours(6))
            ->orderBy('transaction_id')
            ->get()
            ->each(function (Transaction $transaction) use ($notificationService): void {
                $notificationService->sendOnce(
                    (int) $transaction->buyer_id,
                    'PENGINGAT_PELUNASAN',
                    'Segera lunasi lelang ' . $transaction->auction->judul . ' sebelum '
                        . $transaction->batas_pelunasan->format('d M Y H:i') . '.',
                    (int) $transaction->auction_id,
                );
            });

        $releasedTransactions = 0;
        Transaction::query()
            ->where('status', 'ESCROW')
            ->whereNotNull('batas_konfirmasi')
            ->where('batas_konfirmasi', '<=', now())
            ->orderBy('transaction_id')
            ->get()
            ->each(function (Transaction $transaction) use ($settlementService, &$releasedTransactions): void {
                $settlementService->release($transaction);
                $releasedTransactions++;
            });

        $this->info(sprintf(
            'Lelang diaktifkan: %d; lelang diproses: %d; pelunasan kedaluwarsa: %d; escrow dicairkan: %d.',
            $startedAuctions,
            $closedAuctions,
            $expiredTransactions,
            $releasedTransactions,
        ));

        return self::SUCCESS;
    }
}
