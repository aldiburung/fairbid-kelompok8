<?php

namespace App\Services;

use App\Models\AuctionDeposit;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function handleWebhook(Payment $payment, string $status): bool
    {
        if (! in_array($status, ['berhasil', 'gagal'], true)) {
            throw new InvalidArgumentException('Status webhook pembayaran tidak valid.');
        }

        return DB::transaction(function () use ($payment, $status): bool {
            $lockedPayment = Payment::query()
                ->whereKey($payment->payment_id)
                ->lockForUpdate()
                ->first();

            if (! $lockedPayment || $lockedPayment->status !== 'PENDING') {
                return false;
            }

            if ($status === 'berhasil') {
                $lockedPayment->update([
                    'status' => 'BERHASIL',
                    'webhook_at' => now(),
                ]);

                if ($lockedPayment->deposit_id !== null) {
                    $deposit = AuctionDeposit::query()
                        ->whereKey($lockedPayment->deposit_id)
                        ->lockForUpdate()
                        ->first();

                    if ($deposit) {
                        $deposit->update([
                            'status' => 'DITAHAN',
                            'paid_at' => now(),
                        ]);
                    }
                }
            } else {
                $lockedPayment->update([
                    'status' => 'GAGAL',
                    'webhook_at' => now(),
                ]);
            }

            return true;
        });
    }
}
