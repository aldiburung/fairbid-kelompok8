<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\AuctionDeposit;
use App\Models\Bid;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SettlementService
{
    public function closeAuction(Auction $a): void
    {
        DB::transaction(function () use ($a): void {
            $auction = Auction::query()
                ->whereKey($a->auction_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($auction->status !== 'AKTIF' || $auction->end_time->isFuture()) {
                return;
            }

            $winningBid = Bid::query()
                ->where('auction_id', $auction->auction_id)
                ->where('status', 'VALID')
                ->orderByDesc('amount')
                ->orderBy('sequence_no')
                ->lockForUpdate()
                ->first();

            if ($winningBid && (
                $auction->harga_limit === null
                || (float) $winningBid->amount >= (float) $auction->harga_limit
            )) {
                $auction->winning_bid_id = $winningBid->bid_id;
                $auction->status = 'SELESAI';
                $auction->save();

                $hargaAkhir = (float) $winningBid->amount;
                $feeAmount = round($hargaAkhir * 0.03, 2);
                $transaction = Transaction::query()->create([
                    'auction_id' => $auction->auction_id,
                    'seller_id' => $auction->seller_id,
                    'buyer_id' => $winningBid->bidder_id,
                    'harga_akhir' => $winningBid->amount,
                    'fee_persen' => 3.00,
                    'fee_amount' => $feeAmount,
                    'net_seller' => round($hargaAkhir - $feeAmount, 2),
                    'status' => 'MENUNGGU_PELUNASAN',
                    'batas_pelunasan' => $auction->end_time->copy()->addHours(24),
                ]);

                $this->refundHeldDeposits($auction, $winningBid->bidder_id, $transaction);

                return;
            }

            $auction->winning_bid_id = null;
            $auction->status = 'TANPA_PEMENANG';
            $auction->save();

            $this->refundHeldDeposits($auction, null, null);
        });
    }

    public function expireUnpaid(Transaction $t): void
    {
        DB::transaction(function () use ($t): void {
            $transaction = Transaction::query()
                ->whereKey($t->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $transaction->status !== 'MENUNGGU_PELUNASAN'
                || $transaction->batas_pelunasan->isFuture()
            ) {
                return;
            }

            $deposit = $this->winnerDeposit($transaction);
            $deposit->update([
                'status' => 'DIPOTONG',
                'settled_at' => now(),
            ]);

            $transaction->status = 'BATAL';
            $transaction->save();

            $this->createSettlementPayment(
                $transaction,
                $deposit,
                $transaction->buyer_id,
                'POTONG_UJL',
                (float) $deposit->nominal_ujl,
            );
        });
    }

    public function payOff(Transaction $t): void
    {
        DB::transaction(function () use ($t): void {
            $transaction = Transaction::query()
                ->whereKey($t->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->status !== 'MENUNGGU_PELUNASAN') {
                return;
            }

            $payment = Payment::query()
                ->where('transaction_id', $transaction->transaction_id)
                ->where('jenis', 'PELUNASAN')
                ->where('status', 'PENDING')
                ->orderByDesc('payment_id')
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return;
            }

            $deposit = $this->winnerDeposit($transaction);
            $now = now();

            $payment->update([
                'status' => 'BERHASIL',
                'webhook_at' => $now,
            ]);
            $deposit->update([
                'status' => 'DIPOTONG',
                'settled_at' => $now,
            ]);
            $transaction->status = 'ESCROW';
            $transaction->save();
        });
    }

    public function release(Transaction $t): void
    {
        DB::transaction(function () use ($t): void {
            $transaction = Transaction::query()
                ->whereKey($t->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->status !== 'ESCROW') {
                return;
            }

            $now = now();
            if ($transaction->buyer_confirmed_at === null) {
                $transaction->buyer_confirmed_at = $transaction->batas_konfirmasi ?? $now;
            }
            $transaction->released_at = $now;
            $transaction->status = 'SELESAI';
            $transaction->save();

            $this->createSettlementPayment(
                $transaction,
                null,
                $transaction->seller_id,
                'PENCAIRAN',
                (float) $transaction->net_seller,
            );
        });
    }

    private function refundHeldDeposits(Auction $auction, int|string|null $excludedUserId, ?Transaction $transaction): void
    {
        $deposits = AuctionDeposit::query()
            ->where('auction_id', $auction->auction_id)
            ->where('status', 'DITAHAN')
            ->when($excludedUserId !== null, fn ($query) => $query->where('user_id', '!=', $excludedUserId))
            ->lockForUpdate()
            ->get();

        foreach ($deposits as $deposit) {
            $deposit->update([
                'status' => 'DIKEMBALIKAN',
                'settled_at' => now(),
            ]);

            $this->createSettlementPayment(
                $transaction,
                $deposit,
                $deposit->user_id,
                'REFUND_UJL',
                (float) $deposit->nominal_ujl,
            );
        }
    }

    private function winnerDeposit(Transaction $transaction): AuctionDeposit
    {
        $deposit = AuctionDeposit::query()
            ->where('auction_id', $transaction->auction_id)
            ->where('user_id', $transaction->buyer_id)
            ->where('status', 'DITAHAN')
            ->lockForUpdate()
            ->first();

        if (! $deposit) {
            throw new RuntimeException('Deposit UJL pemenang tidak ditemukan atau sudah diselesaikan.');
        }

        return $deposit;
    }

    private function createSettlementPayment(
        ?Transaction $transaction,
        ?AuctionDeposit $deposit,
        int|string $userId,
        string $jenis,
        float $amount,
    ): Payment {
        $uuid = (string) Str::uuid();

        return Payment::query()->create([
            'user_id' => $userId,
            'deposit_id' => $deposit?->deposit_id,
            'transaction_id' => $transaction?->transaction_id,
            'jenis' => $jenis,
            'amount' => $amount,
            'gateway_ref' => ($jenis === 'REFUND_UJL' ? 'SIM-REFUND-' : 'SIM-') . $uuid,
            'idempotency_key' => (string) Str::uuid(),
            'status' => 'BERHASIL',
            'webhook_at' => now(),
        ]);
    }
}
