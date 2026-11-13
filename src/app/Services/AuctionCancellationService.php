<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\AuctionDeposit;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AuctionCancellationService
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function cancel(Auction $a, string $statusAkhir, ?string $alasan = null): bool
    {
        if (! in_array($statusAkhir, ['DIBATALKAN', 'DITURUNKAN'], true)) {
            throw new InvalidArgumentException('Status akhir pembatalan lelang tidak valid.');
        }

        return DB::transaction(function () use ($a, $statusAkhir): bool {
            $auction = Auction::query()
                ->whereKey($a->auction_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($auction->status, ['DRAFT', 'TERJADWAL', 'AKTIF'], true)) {
                return false;
            }

            $now = now();
            $auction->status = $statusAkhir;
            $auction->save();

            $deposits = AuctionDeposit::query()
                ->where('auction_id', $auction->auction_id)
                ->lockForUpdate()
                ->get();

            foreach ($deposits as $deposit) {
                if ($deposit->status === 'DITAHAN') {
                    $deposit->update([
                        'status' => 'DIKEMBALIKAN',
                        'settled_at' => $now,
                    ]);

                    $uuid = (string) Str::uuid();
                    Payment::query()->create([
                        'user_id' => $deposit->user_id,
                        'deposit_id' => $deposit->deposit_id,
                        'transaction_id' => null,
                        'jenis' => 'REFUND_UJL',
                        'amount' => $deposit->nominal_ujl,
                        'gateway_ref' => 'SIM-REFUND-' . $uuid,
                        'idempotency_key' => (string) Str::uuid(),
                        'status' => 'BERHASIL',
                        'webhook_at' => $now,
                    ]);
                } elseif ($deposit->status === 'MENUNGGU') {
                    $deposit->update(['status' => 'GAGAL']);
                }
            }

            $isiNotifikasi = 'Lelang ' . $auction->judul . ' dibatalkan. UJL Anda dikembalikan.';
            $peserta = $deposits
                ->pluck('user_id')
                ->push($auction->seller_id)
                ->unique();

            foreach ($peserta as $userId) {
                $this->notificationService->send(
                    (int) $userId,
                    'HASIL_LELANG',
                    $isiNotifikasi,
                    (int) $auction->auction_id,
                );
            }

            return true;
        });
    }
}
