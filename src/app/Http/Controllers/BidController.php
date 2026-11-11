<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionDeposit;
use App\Models\Bid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BidController extends Controller
{
    public function store(Request $request, Auction $auction): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ]);
        $user = $request->user();

        $result = DB::transaction(function () use ($auction, $user, $validated): array {
            $lockedAuction = Auction::query()
                ->whereKey($auction->auction_id)
                ->lockForUpdate()
                ->firstOrFail();
            $now = now();

            if ($lockedAuction->status === 'TERJADWAL' && $lockedAuction->start_time->gt($now)) {
                return [
                    'error' => 'Lelang belum dimulai. Penawaran dibuka pada '
                        . $lockedAuction->start_time->format('d M Y H:i') . '.',
                ];
            }

            if ($lockedAuction->status === 'TERJADWAL' && $lockedAuction->start_time->lte($now)) {
                $lockedAuction->status = 'AKTIF';
                $lockedAuction->save();
            }

            if (
                ($lockedAuction->status === 'AKTIF' && $now->gt($lockedAuction->end_time))
                || in_array($lockedAuction->status, ['SELESAI', 'TANPA_PEMENANG'], true)
            ) {
                return ['error' => 'Lelang telah berakhir.'];
            }

            if ($lockedAuction->status !== 'AKTIF') {
                return ['error' => 'Lelang ini tidak dapat ditawar.'];
            }

            if ((string) $user->user_id === (string) $lockedAuction->seller_id) {
                return ['error' => 'Anda tidak dapat menawar lelang sendiri.'];
            }

            $deposit = AuctionDeposit::query()
                ->where('auction_id', $lockedAuction->auction_id)
                ->where('user_id', $user->user_id)
                ->where('status', 'DITAHAN')
                ->lockForUpdate()
                ->first();

            if (! $deposit) {
                return ['error' => 'UJL Anda belum dibayar.'];
            }

            $highestBid = Bid::query()
                ->where('auction_id', $lockedAuction->auction_id)
                ->where('status', 'VALID')
                ->orderByDesc('amount')
                ->first();
            $minimumAmount = $highestBid
                ? round((float) $highestBid->amount + (float) $lockedAuction->kelipatan_bid, 2)
                : (float) $lockedAuction->harga_awal;

            if (round((float) $validated['amount'], 2) < $minimumAmount) {
                return [
                    'error' => 'Nominal minimal Rp ' . number_format($minimumAmount, 2, ',', '.') . '.',
                ];
            }

            $lastSequence = Bid::query()
                ->where('auction_id', $lockedAuction->auction_id)
                ->max('sequence_no');
            $isExtended = $lockedAuction->end_time->lessThanOrEqualTo(
                $now->copy()->addMinutes($lockedAuction->anti_sniping_window_menit),
            );

            if ($isExtended) {
                $lockedAuction->end_time = $lockedAuction->end_time
                    ->copy()
                    ->addMinutes($lockedAuction->anti_sniping_extend_menit);
                $lockedAuction->save();
            }

            Bid::create([
                'auction_id' => $lockedAuction->auction_id,
                'bidder_id' => $user->user_id,
                'deposit_id' => $deposit->deposit_id,
                'amount' => $validated['amount'],
                'sequence_no' => (int) $lastSequence + 1,
                'server_timestamp' => $now,
                'status' => 'VALID',
                'extended_auction' => $isExtended,
            ]);

            return ['error' => null];
        });

        if ($result['error'] !== null) {
            return back()->withErrors(['amount' => $result['error']])->withInput();
        }

        return back()->with('success', 'Penawaran Anda diterima.');
    }
}
