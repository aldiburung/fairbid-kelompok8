@extends('layouts.app')

@section('title', 'Transaksi #' . $transaction->transaction_id . ' - FairBid')

@section('content')
@php
    $statusClass = match ($transaction->status) {
        'SELESAI' => 'text-bg-success',
        'ESCROW' => 'text-bg-primary',
        'BATAL', 'DIKEMBALIKAN' => 'text-bg-secondary',
        'SENGKETA' => 'text-bg-danger',
        default => 'text-bg-warning',
    };
    $sisaBayar = (float) $transaction->harga_akhir - (float) $deposit->nominal_ujl;
@endphp
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="mb-4">
                <p class="text-uppercase small fw-semibold text-secondary mb-1">Rincian transaksi #{{ $transaction->transaction_id }}</p>
                <h1 class="h2 fw-bold text-fairbid mb-2">{{ $transaction->auction->judul }}</h1>
                <span class="badge rounded-pill {{ $statusClass }} px-3 py-2">{{ str_replace('_', ' ', $transaction->status) }}</span>
            </div>

            <article class="card fairbid-card mb-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="h5 fw-bold text-fairbid mb-4">Rincian pembayaran</h2>
                    <div class="d-flex justify-content-between gap-3 mb-3">
                        <span class="text-secondary">Harga akhir lelang</span>
                        <span class="fw-semibold">Rp{{ number_format((float) $transaction->harga_akhir, 0, ',', '.') }}</span>
                    </div>
                    @if ($isBuyer)
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-secondary">Dikurangi UJL yang sudah Anda bayar</span>
                            <span class="text-secondary">-Rp{{ number_format((float) $deposit->nominal_ujl, 0, ',', '.') }}</span>
                        </div>
                        <div class="border-top mt-3 pt-3">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                <span class="text-secondary">Yang harus dibayar sekarang</span>
                                <span class="fs-4 fw-bold">Rp{{ number_format($sisaBayar, 0, ',', '.') }}</span>
                            </div>
                            <div class="d-flex justify-content-between gap-3">
                                <span class="text-secondary">Batas pembayaran</span>
                                <span>{{ $transaction->batas_pelunasan->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                        <p class="text-secondary small mb-0 mt-3">UJL Anda sudah dihitung sebagai bagian dari pembayaran.</p>
                    @elseif ($isSeller)
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-secondary">Biaya layanan FairBid ({{ number_format((float) $transaction->fee_persen, 2, ',', '.') }}%)</span>
                            <span class="text-secondary">-Rp{{ number_format((float) $transaction->fee_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="border-top mt-3 pt-3">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <span class="text-secondary">Dana yang Anda terima</span>
                                <span class="fs-4 fw-bold">Rp{{ number_format((float) $transaction->net_seller, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <p class="text-secondary small mb-0 mt-3">Dana dicairkan setelah pembeli mengonfirmasi barang diterima, atau otomatis 3 hari setelah serah terima.</p>
                    @endif
                </div>
            </article>

            <div class="d-flex flex-column flex-sm-row gap-2">
                @if ($isBuyer && $transaction->status === 'MENUNGGU_PELUNASAN' && now()->lte($transaction->batas_pelunasan))
                    <form action="{{ route('transactions.pay', $transaction) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-orange fw-semibold">Bayar pelunasan</button>
                    </form>
                @endif
                @if ($isBuyer && $transaction->status === 'ESCROW' && $transaction->seller_confirmed_at)
                    <form action="{{ route('transactions.received', $transaction) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-orange fw-semibold">Konfirmasi barang diterima</button>
                    </form>
                @endif
                @if ($isSeller && $transaction->status === 'ESCROW')
                    <form action="{{ route('transactions.handover', $transaction) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-fairbid fw-semibold">Tandai barang diserahkan</button>
                    </form>
                @endif
            </div>

            @if (app()->environment('local') && $isBuyer && $transaction->status === 'MENUNGGU_PELUNASAN' && $latestPayment?->jenis === 'PELUNASAN' && $latestPayment->status === 'PENDING')
                <div class="card fairbid-card mt-4">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold text-fairbid mb-3">Simulasi pembayaran lokal</h2>
                        <div class="d-grid d-sm-flex gap-2">
                            <form action="{{ route('sandbox.transactions.simulasi', $transaction) }}" method="POST" class="flex-fill">
                                @csrf
                                <input type="hidden" name="hasil" value="berhasil">
                                <button type="submit" class="btn btn-orange fw-semibold w-100">Simulasikan bayar berhasil</button>
                            </form>
                            <form action="{{ route('sandbox.transactions.simulasi', $transaction) }}" method="POST" class="flex-fill">
                                @csrf
                                <input type="hidden" name="hasil" value="gagal">
                                <button type="submit" class="btn btn-outline-secondary fw-semibold w-100">Simulasikan bayar gagal</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <a href="{{ route('transactions.index') }}" class="btn btn-link text-fairbid px-0 mt-3">&larr; Kembali ke transaksi</a>
        </div>
    </div>
</section>
@endsection
