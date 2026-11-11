<?php

namespace App\Http\Controllers;

use App\Models\AuctionDeposit;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->user_id;
        $transactions = Transaction::query()
            ->with('auction')
            ->where(function ($query) use ($userId): void {
                $query->where('seller_id', $userId)->orWhere('buyer_id', $userId);
            })
            ->orderByDesc('transaction_id')
            ->paginate(10);

        return view('transactions.index', compact('transactions'));
    }

    public function show(Request $request, Transaction $transaction): View
    {
        $this->authorizeParticipant($request, $transaction);
        $transaction->load(['auction', 'seller', 'buyer', 'payments']);

        $deposit = AuctionDeposit::query()
            ->where('auction_id', $transaction->auction_id)
            ->where('user_id', $transaction->buyer_id)
            ->firstOrFail();
        $latestPayment = $transaction->payments
            ->sortByDesc('payment_id')
            ->first();

        return view('transactions.show', [
            'transaction' => $transaction,
            'deposit' => $deposit,
            'latestPayment' => $latestPayment,
            'isBuyer' => (string) $transaction->buyer_id === (string) $request->user()->user_id,
            'isSeller' => (string) $transaction->seller_id === (string) $request->user()->user_id,
        ]);
    }

    public function pay(Request $request, Transaction $transaction): RedirectResponse
    {
        abort_unless((string) $transaction->buyer_id === (string) $request->user()->user_id, 403);

        $payment = DB::transaction(function () use ($request, $transaction): Payment {
            $lockedTransaction = Transaction::query()
                ->whereKey($transaction->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedTransaction->status === 'MENUNGGU_PELUNASAN'
                && now()->lte($lockedTransaction->batas_pelunasan),
                403,
            );

            $pendingPayment = Payment::query()
                ->where('transaction_id', $lockedTransaction->transaction_id)
                ->where('jenis', 'PELUNASAN')
                ->where('status', 'PENDING')
                ->orderByDesc('payment_id')
                ->first();

            if ($pendingPayment) {
                return $pendingPayment;
            }

            $deposit = AuctionDeposit::query()
                ->where('auction_id', $lockedTransaction->auction_id)
                ->where('user_id', $lockedTransaction->buyer_id)
                ->where('status', 'DITAHAN')
                ->lockForUpdate()
                ->firstOrFail();
            $amount = round(
                (float) $lockedTransaction->harga_akhir - (float) $deposit->nominal_ujl,
                2,
            );

            return Payment::query()->create([
                'user_id' => $request->user()->user_id,
                'deposit_id' => $deposit->deposit_id,
                'transaction_id' => $lockedTransaction->transaction_id,
                'jenis' => 'PELUNASAN',
                'amount' => $amount,
                'gateway_ref' => 'SIM-PAY-' . Str::uuid(),
                'idempotency_key' => (string) Str::uuid(),
                'status' => 'PENDING',
            ]);
        });

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Pembayaran pelunasan siap disimulasikan.');
    }

    public function simulasi(
        Request $request,
        Transaction $transaction,
        SettlementService $settlementService,
    ): RedirectResponse {
        abort_unless((string) $transaction->buyer_id === (string) $request->user()->user_id, 403);
        abort_unless(app()->environment('local'), 404);

        $validated = $request->validate([
            'hasil' => ['required', Rule::in(['berhasil', 'gagal'])],
        ]);

        $paymentId = DB::transaction(function () use ($transaction, $validated): int {
            $lockedTransaction = Transaction::query()
                ->whereKey($transaction->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedTransaction->status === 'MENUNGGU_PELUNASAN'
                && now()->lte($lockedTransaction->batas_pelunasan),
                403,
            );

            $payment = Payment::query()
                ->where('transaction_id', $lockedTransaction->transaction_id)
                ->where('jenis', 'PELUNASAN')
                ->where('status', 'PENDING')
                ->orderByDesc('payment_id')
                ->lockForUpdate()
                ->firstOrFail();

            if ($validated['hasil'] === 'gagal') {
                $payment->update([
                    'status' => 'GAGAL',
                    'webhook_at' => now(),
                ]);
            }

            return $payment->payment_id;
        });

        if ($validated['hasil'] === 'berhasil') {
            $settlementService->payOff($transaction);
        }

        $payment = Payment::query()->findOrFail($paymentId);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with(
                $payment->status === 'BERHASIL' ? 'success' : 'payment_error',
                $payment->status === 'BERHASIL'
                    ? 'Pelunasan berhasil dan dana masuk ke escrow.'
                    : 'Pembayaran pelunasan gagal.',
            );
    }

    public function confirmHandover(Request $request, Transaction $transaction): RedirectResponse
    {
        abort_unless((string) $transaction->seller_id === (string) $request->user()->user_id, 403);

        DB::transaction(function () use ($transaction): void {
            $lockedTransaction = Transaction::query()
                ->whereKey($transaction->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($lockedTransaction->status === 'ESCROW', 403);

            if ($lockedTransaction->seller_confirmed_at === null) {
                $now = now();
                $lockedTransaction->seller_confirmed_at = $now;
                $lockedTransaction->batas_konfirmasi = $now->copy()->addDays(3);
                $lockedTransaction->save();
            }
        });

        return back()->with('success', 'Serah terima telah ditandai.');
    }

    public function confirmReceived(
        Request $request,
        Transaction $transaction,
        SettlementService $settlementService,
    ): RedirectResponse {
        abort_unless((string) $transaction->buyer_id === (string) $request->user()->user_id, 403);

        DB::transaction(function () use ($transaction): void {
            $lockedTransaction = Transaction::query()
                ->whereKey($transaction->transaction_id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless(
                $lockedTransaction->status === 'ESCROW'
                && $lockedTransaction->seller_confirmed_at !== null,
                403,
            );

            if ($lockedTransaction->buyer_confirmed_at === null) {
                $lockedTransaction->buyer_confirmed_at = now();
                $lockedTransaction->save();
            }
        });

        $settlementService->release($transaction);

        return back()->with('success', 'Penerimaan barang dikonfirmasi dan dana telah dicairkan.');
    }

    private function authorizeParticipant(Request $request, Transaction $transaction): void
    {
        abort_unless(
            (string) $transaction->buyer_id === (string) $request->user()->user_id
            || (string) $transaction->seller_id === (string) $request->user()->user_id,
            403,
        );
    }
}
