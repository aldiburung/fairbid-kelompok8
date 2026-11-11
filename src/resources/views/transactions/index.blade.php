@extends('layouts.app')

@section('title', 'Transaksi - FairBid')

@section('content')
<section class="container py-5">
    <div class="mb-4">
        <p class="text-uppercase small fw-semibold text-secondary mb-1">Riwayat transaksi</p>
        <h1 class="h2 fw-bold text-fairbid mb-0">Transaksi saya</h1>
    </div>

    <div class="card fairbid-card">
        <div class="card-body p-0">
            @forelse ($transactions as $transaction)
                @php
                    $statusClass = match ($transaction->status) {
                        'SELESAI' => 'text-bg-success',
                        'ESCROW' => 'text-bg-primary',
                        'BATAL', 'DIKEMBALIKAN' => 'text-bg-secondary',
                        'SENGKETA' => 'text-bg-danger',
                        default => 'text-bg-warning',
                    };
                @endphp
                <a href="{{ route('transactions.show', $transaction) }}" class="d-block text-decoration-none text-dark border-bottom p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                        <div>
                            <h2 class="h5 fw-bold text-fairbid mb-1">{{ $transaction->auction->judul }}</h2>
                            <div class="small text-secondary">
                                {{ (string) $transaction->buyer_id === (string) auth()->user()->user_id ? 'Anda sebagai pembeli' : 'Anda sebagai penjual' }}
                                &middot; Transaksi #{{ $transaction->transaction_id }}
                            </div>
                        </div>
                        <div class="text-md-end">
                            <span class="badge rounded-pill {{ $statusClass }}">{{ str_replace('_', ' ', $transaction->status) }}</span>
                            <div class="fw-semibold mt-2">Rp{{ number_format((float) $transaction->harga_akhir, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-4 text-secondary">Belum ada transaksi.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">{{ $transactions->links() }}</div>
</section>
@endsection
