<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Transaction;
use App\Services\SettlementService;
use Illuminate\Console\Command;

class CloseAuctions extends Command
{
    protected $signature = 'auctions:close';

    protected $description = 'Memproses penutupan lelang dan settlement transaksi.';

    public function handle(SettlementService $settlementService): int
    {
        $startedAuctions = Auction::query()
            ->where('status', 'TERJADWAL')
            ->where('start_time', '<=', now())
            ->update(['status' => 'AKTIF']);

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
