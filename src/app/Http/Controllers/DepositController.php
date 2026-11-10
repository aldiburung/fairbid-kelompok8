<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionDeposit;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function join(Request $request, Auction $auction): RedirectResponse
    {
        $user = $request->user();

        if ($user->status_verifikasi !== 'DISETUJUI') {
            return back()
                ->withErrors(['status_verifikasi' => 'Anda harus terverifikasi untuk mengikuti lelang.'])
                ->withInput();
        }

        if ((string) $user->user_id === (string) $auction->seller_id) {
            return back()
                ->withErrors(['auction' => 'Penjual tidak dapat mengikuti lelangnya sendiri.'])
                ->withInput();
        }

        if (
            ! in_array($auction->status, ['AKTIF', 'TERJADWAL'], true)
            || $auction->end_time->isPast()
        ) {
            return back()
                ->withErrors(['auction' => 'Lelang ini tidak dapat diikuti.'])
                ->withInput();
        }

        $hasHeldDeposit = AuctionDeposit::query()
            ->where('auction_id', $auction->auction_id)
            ->where('user_id', $user->user_id)
            ->where('status', 'DITAHAN')
            ->exists();

        if ($hasHeldDeposit) {
            return back()
                ->withErrors(['auction' => 'Anda sudah mengikuti lelang ini.'])
                ->withInput();
        }

        $request->validate([
            'terms_accepted' => ['accepted'],
        ], [
            'terms_accepted.accepted' => 'Anda harus menyetujui syarat untuk mengikuti lelang.',
        ]);

        $result = DB::transaction(function () use ($auction, $user): array {
            $deposit = AuctionDeposit::query()
                ->where('auction_id', $auction->auction_id)
                ->where('user_id', $user->user_id)
                ->lockForUpdate()
                ->first();

            if ($deposit?->status === 'DITAHAN') {
                return ['deposit' => $deposit, 'error' => 'Anda sudah mengikuti lelang ini.'];
            }

            if ($deposit && $deposit->status !== 'MENUNGGU') {
                return [
                    'deposit' => $deposit,
                    'error' => 'Deposit untuk lelang ini sudah diproses dan tidak dapat digunakan kembali.',
                ];
            }

            if (! $deposit) {
                $deposit = AuctionDeposit::query()->firstOrCreate(
                    [
                        'auction_id' => $auction->auction_id,
                        'user_id' => $user->user_id,
                    ],
                    [
                        'nominal_ujl' => $auction->nilai_ujl,
                        'status' => 'MENUNGGU',
                        'terms_accepted_at' => now(),
                    ],
                );

                $deposit = AuctionDeposit::query()
                    ->whereKey($deposit->deposit_id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            if ($deposit->status === 'DITAHAN') {
                return ['deposit' => $deposit, 'error' => 'Anda sudah mengikuti lelang ini.'];
            }

            if ($deposit->status !== 'MENUNGGU') {
                return [
                    'deposit' => $deposit,
                    'error' => 'Deposit untuk lelang ini sudah diproses dan tidak dapat digunakan kembali.',
                ];
            }

            $hasPendingPayment = $deposit->payments()
                ->where('status', 'PENDING')
                ->exists();

            if (! $hasPendingPayment) {
                $this->createPendingPayment($deposit, $user->user_id);
            }

            return ['deposit' => $deposit, 'error' => null];
        });

        if ($result['error'] !== null) {
            return back()->withErrors(['auction' => $result['error']])->withInput();
        }

        return redirect()->route('deposits.pay', $result['deposit']);
    }

    public function pay(Request $request, AuctionDeposit $deposit): View
    {
        abort_unless((string) $deposit->user_id === (string) $request->user()->user_id, 403);

        $deposit->load('auction');

        return view('deposits.pay', [
            'deposit' => $deposit,
            'auction' => $deposit->auction,
            'latestPayment' => $deposit->payments()->orderByDesc('payment_id')->first(),
        ]);
    }

    public function simulasi(
        Request $request,
        Payment $payment,
        PaymentService $paymentService,
    ): RedirectResponse {
        abort_unless(app()->environment('local'), 404);
        abort_unless((string) $payment->user_id === (string) $request->user()->user_id, 403);
        abort_if($payment->deposit_id === null, 404);
        abort_unless($payment->jenis === 'UJL', 404);
        abort_unless(
            (string) $payment->deposit->user_id === (string) $request->user()->user_id,
            403,
        );

        $validated = $request->validate([
            'hasil' => ['required', Rule::in(['berhasil', 'gagal'])],
        ]);

        DB::transaction(function () use ($payment, $paymentService, $validated): void {
            $processed = $paymentService->handleWebhook($payment, $validated['hasil']);

            if ($processed && $validated['hasil'] === 'gagal') {
                $this->createPendingPayment($payment->deposit, $payment->user_id);
            }
        });

        $payment->refresh();
        $message = $payment->status === 'BERHASIL'
            ? 'Pembayaran UJL berhasil. Anda dapat menawar.'
            : 'Pembayaran gagal atau kedaluwarsa. Silakan coba lagi.';

        $redirect = redirect()->route('deposits.pay', $payment->deposit_id);

        return $payment->status === 'BERHASIL'
            ? $redirect->with('success', $message)
            : $redirect->with('payment_error', $message);
    }

    private function createPendingPayment(AuctionDeposit $deposit, int|string $userId): Payment
    {
        return Payment::create([
            'user_id' => $userId,
            'deposit_id' => $deposit->deposit_id,
            'jenis' => 'UJL',
            'amount' => $deposit->nominal_ujl,
            'gateway_ref' => 'SIM-' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'status' => 'PENDING',
        ]);
    }
}
