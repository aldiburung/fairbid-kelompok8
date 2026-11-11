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

                    @if ($auction->status === 'SELESAI')
                        <div class="alert alert-success">
                            Lelang berakhir. Harga akhir Rp{{ number_format((float) $auction->winningBid?->amount, 0, ',', '.') }}.
                            @if (($isWinner || $isSeller) && $auctionTransaction)
                                <a href="{{ route('transactions.show', $auctionTransaction) }}" class="alert-link">Lihat transaksi</a>
                            @endif
                        </div>
                    @elseif ($auction->status === 'TANPA_PEMENANG')
                        <div class="alert alert-secondary">Lelang berakhir tanpa pemenang.</div>
                    @endif

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
                            <div class="fw-bold text-fairbid" id="harga-tertinggi">Rp{{ number_format((float) $hargaTertinggi, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-secondary">Nominal minimum</div>
                            <div class="fw-semibold" id="nominal-minimum">Rp{{ number_format((float) $nominalMinimum, 0, ',', '.') }}</div>
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
                    @if (session('success'))
                        <div class="alert alert-success py-2">{{ session('success') }}</div>
                    @endif

                    @if (in_array($auction->status, ['SELESAI', 'TANPA_PEMENANG'], true))
                        @if ($isSeller && $auction->status === 'TANPA_PEMENANG')
                            <a href="{{ route('auctions.index') }}" class="btn btn-outline-secondary w-100 mb-4">Kembali ke katalog</a>
                        @endif
                    @elseif ($isSeller)
                        <div class="alert alert-secondary mb-4">Anda adalah penjual dan tidak dapat menawar lelang ini.</div>
                    @elseif (auth()->guest())
                        <div class="alert alert-info mb-2">Silakan masuk untuk menawar lelang ini.</div>
                        <a href="{{ route('login') }}" class="btn btn-orange w-100 fw-semibold mb-4">Masuk</a>
                    @elseif ($userDeposit?->status === 'DITAHAN')
                        <form action="{{ route('bids.store', $auction) }}" method="POST" class="mb-4">
                            @csrf
                            <label for="amount" class="form-label fw-semibold">Nominal penawaran</label>
                            <input
                                type="number"
                                class="form-control @error('amount') is-invalid @enderror"
                                id="amount"
                                name="amount"
                                value="{{ old('amount', number_format((float) $nominalMinimum, 2, '.', '')) }}"
                                min="{{ number_format((float) $nominalMinimum, 2, '.', '') }}"
                                step="0.01"
                                required
                            >
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn btn-orange w-100 fw-semibold mt-3">TAWAR</button>
                        </form>
                    @else
                        <div class="alert alert-warning mb-2">UJL Anda belum dibayar.</div>
                        @if ($userDeposit)
                            <a href="{{ route('deposits.pay', $userDeposit) }}" class="btn btn-orange w-100 fw-semibold mb-4">Bayar UJL</a>
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
                                <button type="submit" class="btn btn-orange w-100 fw-semibold">Ikut lelang dan bayar UJL</button>
                            </form>
                        @endif
                    @endif

                    <h2 class="h5 fw-bold text-fairbid">Deskripsi</h2>
                    <p class="mb-0">{!! nl2br(e($auction->deskripsi)) !!}</p>
                </div>
            </article>

            <article class="card fairbid-card mt-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h5 fw-bold text-fairbid mb-0">Riwayat penawaran</h2>
                        <span class="badge text-bg-warning {{ $hasExtendedBid ? '' : 'd-none' }}" id="extended-bid-badge">Waktu diperpanjang</span>
                    </div>
                    <div class="small text-secondary mb-2">Jumlah penawaran: <span id="jumlah-bid">{{ count($bidHistory) }}</span></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Urutan</th>
                                    <th>Nominal</th>
                                    <th>Waktu</th>
                                    <th>Penawar</th>
                                </tr>
                            </thead>
                            <tbody id="bid-history">
                                @forelse ($bidHistory as $bid)
                                    <tr>
                                        <td>{{ $bid['sequence_no'] }}</td>
                                        <td>{{ 'Rp' . number_format($bid['amount'], 0, ',', '.') }}</td>
                                        <td>{{ \Illuminate\Support\Carbon::parse($bid['server_timestamp'])->format('d/m/Y H:i:s') }}</td>
                                        <td>{{ $bid['penawar'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-secondary">Belum ada penawaran.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>
@if (in_array($auction->status, ['AKTIF', 'TERJADWAL'], true))
<script>
    const countdown = document.getElementById('auction-countdown');
    const price = document.getElementById('harga-tertinggi');
    const minimum = document.getElementById('nominal-minimum');
    const bidInput = document.getElementById('amount');
    const bidCount = document.getElementById('jumlah-bid');
    const bidHistory = document.getElementById('bid-history');
    const extendedBidBadge = document.getElementById('extended-bid-badge');
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
            minimum.textContent = formatCurrency.format(Number(state.nominal_minimum));
            bidCount.textContent = String(state.jumlah_bid);
            extendedBidBadge.classList.toggle('d-none', !state.has_extended_bid);
            if (bidInput) {
                const nextMinimum = Number(state.nominal_minimum).toFixed(2);
                bidInput.min = nextMinimum;
                if (document.activeElement !== bidInput) {
                    bidInput.value = nextMinimum;
                }
            }
            renderBidHistory(state.riwayat);
            renderCountdown();
        } catch (error) {
            console.error('Gagal memperbarui status lelang:', error);
        }
    }

    function renderBidHistory(entries) {
        bidHistory.replaceChildren();
        if (entries.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'text-secondary';
            cell.textContent = 'Belum ada penawaran.';
            row.appendChild(cell);
            bidHistory.appendChild(row);
            return;
        }

        entries.forEach((bid) => {
            const row = document.createElement('tr');
            [
                bid.sequence_no,
                formatCurrency.format(Number(bid.amount)),
                new Date(bid.server_timestamp).toLocaleString('id-ID'),
                bid.penawar,
            ].forEach((value) => {
                const cell = document.createElement('td');
                cell.textContent = String(value);
                row.appendChild(cell);
            });
            bidHistory.appendChild(row);
        });
    }

    renderCountdown();
    window.setInterval(() => {
        remainingSeconds = Math.max(0, remainingSeconds - 1);
        renderCountdown();
    }, 1000);
    window.setInterval(refreshAuctionState, 3000);
</script>
@endif
@endsection
