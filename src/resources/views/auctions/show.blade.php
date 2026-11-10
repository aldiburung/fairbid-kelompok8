@extends('layouts.app')

@section('title', $auction->judul . ' - FairBid')

@section('content')
<section class="container py-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="row g-3">
                @forelse ($auction->auctionMedia as $media)
                    <div class="{{ $loop->first ? 'col-12' : 'col-6' }}">
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->url) }}"
                            alt="{{ $auction->judul }} - foto {{ $loop->iteration }}"
                            class="img-fluid w-100 rounded-4 shadow-sm"
                            style="max-height: {{ $loop->first ? '480px' : '220px' }}; object-fit: cover;"
                        >
                    </div>
                @empty
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-center rounded-4 bg-white text-secondary" style="height: 320px;">Foto belum tersedia</div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-5">
            <article class="card fairbid-card">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <span class="badge rounded-pill {{ $auction->model_transaksi === 'C2C' ? 'text-bg-primary' : 'text-bg-warning' }}">{{ $auction->model_transaksi }}</span>
                        <span class="badge rounded-pill {{ $auction->status === 'AKTIF' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $auction->status === 'AKTIF' ? 'Aktif' : ($auction->status === 'TERJADWAL' ? 'Terjadwal' : $auction->status) }}</span>
                    </div>
                    <h1 class="h2 fw-bold text-fairbid mb-3">{{ $auction->judul }}</h1>
                    <p class="text-secondary mb-4">Penjual: <span class="fw-semibold text-dark">{{ $sellerName }}</span></p>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="small text-secondary">Harga awal</div>
                            <div class="fw-semibold">Rp{{ number_format((float) $auction->harga_awal, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-secondary">Kelipatan bid</div>
                            <div class="fw-semibold">Rp{{ number_format((float) $auction->kelipatan_bid, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-secondary">UJL peserta</div>
                            <div class="fw-semibold">Rp{{ number_format((float) $auction->nilai_ujl, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-secondary">Harga saat ini</div>
                            <div class="fw-bold text-fairbid" id="harga-tertinggi" data-value="{{ (float) $auction->harga_awal }}">Rp{{ number_format((float) $auction->harga_awal, 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <span class="fw-semibold">Sisa waktu:</span>
                        <span id="auction-countdown" data-seconds="{{ $initialSeconds }}">{{ $initialSeconds }} detik</span>
                    </div>

                    @if ($errors->has('auction') || $errors->has('status_verifikasi'))
                        <div class="alert alert-danger py-2">
                            {{ $errors->first('auction') ?: $errors->first('status_verifikasi') }}
                        </div>
                    @endif

                    @if ($isSeller)
                        <button type="button" class="btn btn-outline-secondary w-100 mb-4" disabled>Ini lelang Anda</button>
                    @elseif (auth()->guest())
                        <a href="{{ route('login') }}" class="btn btn-orange w-100 fw-semibold mb-4">Masuk untuk Ikut Lelang</a>
                    @elseif ($userDeposit?->status === 'DITAHAN')
                        <div class="mb-4">
                            <span class="badge text-bg-success px-3 py-2">UJL ditahan, siap menawar</span>
                        </div>
                    @elseif ($userDeposit?->status === 'MENUNGGU')
                        <a href="{{ route('deposits.pay', $userDeposit) }}" class="btn btn-orange w-100 fw-semibold mb-4">Lanjutkan pembayaran</a>
                    @else
                        <form action="{{ route('deposits.join', $auction) }}" method="POST" class="mb-4">
                            @csrf
                            <div class="form-check mb-3">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="terms_accepted"
                                    id="terms_accepted"
                                    value="1"
                                    required
                                >
                                <label class="form-check-label small" for="terms_accepted">
                                    Saya menyetujui syarat lelang dan memahami UJL yang harus dibayarkan.
                                </label>
                            </div>
                            @error('terms_accepted')
                                <div class="text-danger small mb-2">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn btn-orange w-100 fw-semibold">Ikut Lelang</button>
                        </form>
                    @endif

                    <h2 class="h5 fw-bold text-fairbid">Deskripsi</h2>
                    <p class="mb-0">{!! nl2br(e($auction->deskripsi)) !!}</p>
                </div>
            </article>

            <article class="card fairbid-card mt-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold text-fairbid mb-3">Riwayat penawaran</h2>
                    <p class="text-secondary mb-0">Belum ada penawaran.</p>
                </div>
            </article>
        </div>
    </div>
</section>
<script>
    const countdown = document.getElementById('auction-countdown');
    const price = document.getElementById('harga-tertinggi');
    const formatCurrency = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    });
    let remainingSeconds = Number(countdown.dataset.seconds);

    function renderCountdown() {
        if (remainingSeconds <= 0) {
            countdown.textContent = 'Berakhir';
            return;
        }

        const days = Math.floor(remainingSeconds / 86400);
        const hours = Math.floor((remainingSeconds % 86400) / 3600);
        const minutes = Math.floor((remainingSeconds % 3600) / 60);
        const seconds = remainingSeconds % 60;
        const time = [hours, minutes, seconds].map((part) => String(part).padStart(2, '0')).join(':');

        countdown.textContent = days > 0 ? `${days} hari ${time}` : time;
    }

    async function refreshAuctionState() {
        try {
            const response = await fetch(@json(route('auctions.state', $auction)), {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                throw new Error(`Permintaan status gagal (${response.status}).`);
            }

            const state = await response.json();
            remainingSeconds = Math.max(0, Number(state.sisa_detik));
            price.textContent = formatCurrency.format(Number(state.harga_tertinggi));
            renderCountdown();
        } catch (error) {
            console.error('Gagal memperbarui status lelang:', error);
        }
    }

    renderCountdown();
    window.setInterval(() => {
        remainingSeconds = Math.max(0, remainingSeconds - 1);
        renderCountdown();
    }, 1000);
    window.setInterval(refreshAuctionState, 5000);
</script>
@endsection
