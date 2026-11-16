<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionDeposit;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function lelangSaya(Request $request): View
    {
        $userId = $request->user()->user_id;

        $sellingAuctions = Auction::query()
            ->where('seller_id', $userId)
            ->withMax(['bids as harga_tertinggi' => function ($query) {
                $query->where('status', 'VALID');
            }], 'amount')
            ->withCount(['bids as jumlah_bid' => function ($query) {
                $query->where('status', 'VALID');
            }])
            ->orderByDesc('created_at')
            ->get();

        $deposits = AuctionDeposit::query()
            ->where('user_id', $userId)
            ->with('auction')
            ->orderByDesc('deposit_id')
            ->get();

        $auctionIds = $deposits->pluck('auction_id')->all();
        $auctionsWithUserBids = Bid::query()
            ->whereIn('auction_id', $auctionIds)
            ->where('bidder_id', $userId)
            ->where('status', 'VALID')
            ->distinct()
            ->pluck('auction_id')
            ->mapWithKeys(static fn ($auctionId): array => [(string) $auctionId => true]);

        $currentLeaders = Bid::query()
            ->select(['auction_id', 'bidder_id'])
            ->whereIn('auction_id', $auctionIds)
            ->where('status', 'VALID')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('bid as challenger')
                    ->whereColumn('challenger.auction_id', 'bid.auction_id')
                    ->where('challenger.status', 'VALID')
                    ->where(function ($query) {
                        $query->whereColumn('challenger.amount', '>', 'bid.amount')
                            ->orWhere(function ($query) {
                                $query->whereColumn('challenger.amount', '=', 'bid.amount')
                                    ->whereColumn('challenger.sequence_no', '<', 'bid.sequence_no');
                            });
                    });
            })
            ->get()
            ->keyBy('auction_id');

        foreach ($deposits as $deposit) {
            $auctionKey = (string) $deposit->auction_id;
            $leaderId = $currentLeaders->get($deposit->auction_id)?->bidder_id;

            $position = (string) $leaderId === (string) $userId
                ? 'Penawar tertinggi'
                : ($auctionsWithUserBids->has($auctionKey) ? 'Tersalip' : 'Belum menawar');

            $deposit->setAttribute('posisi', $position);
        }

        return view('account.lelang-saya', [
            'sellingAuctions' => $sellingAuctions,
            'deposits' => $deposits,
            'activeTab' => $request->query('tab') === 'diikuti' ? 'diikuti' : 'dijual',
        ]);
    }
}
