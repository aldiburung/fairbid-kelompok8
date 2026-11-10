@extends('layouts.app')

@section('title', 'Pembayaran UJL - FairBid')

@section('content')
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            @if (session('payment_error'))
                <div class="alert alert-danger" role="alert">{{ session('payment_error') }}</div>
            @endif

            <div class="mb-4">
                <p class="text-uppercase small fw-semibold text-secondary mb-1">Pembayaran simulasi sandbox</p>
                <h1 class="h2 fw-bold text-fairbid mb-0">Selesaikan UJL</h1>
            </div>

            <div class="card fairbid-card mb-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small text-secondary">Lelang</div>
                            <h2 class="h4 fw-bold text-fairbid mb-0">{{ $auction->judul }}</h2>
                        </div>
                        <div class="text-md-end">
                            <div class="small text-secondary">Status pembayaran terakhir</div>
                            @if ($latestPayment)
                                @php
                                    $statusClass = match ($latestPayment->status) {
                                        'BERHASIL' => 'text-bg-success',
                                        'GAGAL' => 'text-bg-danger',
                                        default => 'text-bg-warning',
                                    };
                                @endphp
                                <span class="badge rounded-pill {{ $statusClass }} px-3 py-2">
                                    {{ $latestPayment->status }}
                                </span>
                            @else
                                <span class="badge rounded-pill text-bg-secondary px-3 py-2">Belum ada pembayaran</span>
                            @endif
                        </div>
                    </div>

                    <div class="border rounded-3 p-3 p-md-4 mb-4">
                        <h3 class="h6 fw-bold text-fairbid mb-3">Rincian pembayaran</h3>
                        <div class="d-flex justify-content-between gap-3 mb-2">
                            <span class="text-secondary">Harga awal</span>
                            <span>Rp{{ number_format((float) $auction->harga_awal, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between gap-3 mb-2">
                            <span class="text-secondary">UJL (10%)</span>
                            <span>Rp{{ number_format((float) $deposit->nominal_ujl, 0, ',', '.') }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between gap-3 fw-bold">
                            <span>Total</span>
                            <span>Rp{{ number_format((float) $auction->harga_awal + (float) $deposit->nominal_ujl, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <h3 class="h6 fw-bold text-fairbid mb-3">Pilih metode pembayaran</h3>
                    <div class="row g-3 mb-4">
                        @foreach (['Virtual Account', 'E-wallet', 'QRIS'] as $method)
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 h-100">
                                    <span class="fw-semibold">{{ $method }}</span>
                                    <div class="small text-secondary mt-1">Tampilan pilihan sandbox</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($latestPayment)
                        <div class="small text-secondary mb-3">
                            Pembayaran #{{ $latestPayment->payment_id }}
                            @if ($latestPayment->created_at)
                                &middot; {{ $latestPayment->created_at->format('d M Y H:i') }}
                            @endif
                        </div>
                    @endif

                    @if (app()->environment('local') && $latestPayment?->status === 'PENDING')
                        <div class="d-grid d-sm-flex gap-2">
                            <form action="{{ route('sandbox.payments.simulasi', $latestPayment) }}" method="POST" class="flex-fill">
                                @csrf
                                <input type="hidden" name="hasil" value="berhasil">
                                <button type="submit" class="btn btn-orange fw-semibold w-100">Simulasikan bayar berhasil</button>
                            </form>
                            <form action="{{ route('sandbox.payments.simulasi', $latestPayment) }}" method="POST" class="flex-fill">
                                @csrf
                                <input type="hidden" name="hasil" value="gagal">
                                <button type="submit" class="btn btn-outline-secondary fw-semibold w-100">Simulasikan bayar gagal</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <a href="{{ route('auctions.show', $auction) }}" class="btn btn-link text-fairbid px-0">&larr; Kembali ke lelang</a>
        </div>
    </div>
</section>
@endsection
