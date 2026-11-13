<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionModeration;
use App\Services\AuctionCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminAuctionController extends Controller
{
    public function index(): View
    {
        $auctions = Auction::query()
            ->with(['seller.personalProfile', 'seller.businessProfile'])
            ->withCount(['bids as jumlah_bid' => function ($query) {
                $query->where('status', 'VALID');
            }])
            ->whereIn('status', ['AKTIF', 'TERJADWAL'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.lelang', compact('auctions'));
    }

    public function turunkan(
        Request $request,
        Auction $auction,
        AuctionCancellationService $cancellationService,
    ): RedirectResponse {
        $validated = $request->validate([
            'alasan' => ['required', 'string'],
        ]);

        $diturunkan = DB::transaction(function () use ($auction, $cancellationService, $request, $validated): bool {
            $lockedAuction = Auction::query()
                ->whereKey($auction->auction_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $cancellationService->cancel($lockedAuction, 'DITURUNKAN', $validated['alasan'])) {
                return false;
            }

            AuctionModeration::query()->create([
                'auction_id' => $lockedAuction->auction_id,
                'admin_id' => $request->user()->user_id,
                'aksi' => 'TURUNKAN',
                'alasan' => $validated['alasan'],
            ]);

            return true;
        });

        if (! $diturunkan) {
            return back()->withErrors(['auction' => 'Lelang ini tidak dapat diturunkan.']);
        }

        return redirect()->route('admin.lelang.index')->with('success', 'Lelang diturunkan.');
    }
}
