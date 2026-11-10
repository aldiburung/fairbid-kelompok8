@extends('layouts.app')

@section('title', 'Katalog lelang - FairBid')

@section('content')
<section class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <span class="text-uppercase small fw-bold text-fairbid">Temukan penawaran</span>
            <h1 class="h2 text-fairbid fw-bold mb-0 mt-1">Katalog lelang</h1>
        </div>
        <span class="text-secondary">{{ $auctions->total() }} lelang ditemukan</span>
    </div>

    <form action="{{ route('auctions.index') }}" method="GET" class="card fairbid-card mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-6 col-lg-4">
                    <label for="q" class="form-label">Cari judul</label>
                    <input type="search" class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Cari lelang...">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->category_id }}" @selected((string) request('category_id') === (string) $category->category_id)>
                                {{ $category->nama_kategori }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="model_transaksi" class="form-label">Koridor</label>
                    <select class="form-select" id="model_transaksi" name="model_transaksi">
                        <option value="">Semua</option>
                        @foreach (['C2C', 'C2B', 'B2B', 'B2C'] as $model)
                            <option value="{{ $model }}" @selected(request('model_transaksi') === $model)>{{ $model }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua</option>
                        <option value="AKTIF" @selected(request('status') === 'AKTIF')>Aktif</option>
                        <option value="TERJADWAL" @selected(request('status') === 'TERJADWAL')>Terjadwal</option>
                    </select>
                </div>
                <div class="col-lg-1 d-grid">
                    <button type="submit" class="btn btn-fairbid">Cari</button>
                </div>
            </div>
        </div>
    </form>

    @if ($auctions->isEmpty())
        <div class="alert alert-info" role="status">Belum ada lelang yang cocok.</div>
    @else
        <div class="row g-4">
            @foreach ($auctions as $auction)
                @php
                    $firstPhoto = $auction->auctionMedia->first();
                @endphp
                <div class="col-sm-6 col-lg-4">
                    <article class="card fairbid-card h-100">
                        @if ($firstPhoto)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($firstPhoto->url) }}" class="card-img-top" alt="{{ $auction->judul }}" style="height: 210px; object-fit: cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light text-secondary" style="height: 210px;">Foto belum tersedia</div>
                        @endif
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                <span class="badge rounded-pill {{ $auction->model_transaksi === 'C2C' ? 'text-bg-primary' : 'text-bg-warning' }}">{{ $auction->model_transaksi }}</span>
                                <span class="badge rounded-pill {{ $auction->status === 'AKTIF' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $auction->status === 'AKTIF' ? 'Aktif' : 'Terjadwal' }}</span>
                            </div>
                            <h2 class="h5 fw-bold text-fairbid">{{ $auction->judul }}</h2>
                            <div class="small text-secondary mb-1">Harga saat ini</div>
                            <div class="fs-5 fw-bold mb-3">Rp{{ number_format((float) ($auction->harga_tertinggi ?? $auction->harga_awal), 0, ',', '.') }}</div>
                            <div class="small text-secondary mb-3">
                                Sisa waktu: <span data-countdown data-end-time="{{ $auction->end_time->toIso8601String() }}">Menghitung...</span>
                            </div>
                            <a href="{{ route('auctions.show', $auction) }}" class="btn btn-outline-primary w-100 fw-semibold mt-auto">Lihat</a>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $auctions->links() }}
        </div>
    @endif
</section>
<script>
    function formatRemainingTime(seconds) {
        if (seconds <= 0) {
            return 'Berakhir';
        }

        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainingSeconds = seconds % 60;
        const time = [hours, minutes, remainingSeconds].map((part) => String(part).padStart(2, '0')).join(':');

        return days > 0 ? `${days} hari ${time}` : time;
    }

    document.querySelectorAll('[data-countdown]').forEach((element) => {
        const endTime = new Date(element.dataset.endTime).getTime();
        const updateCountdown = () => {
            element.textContent = formatRemainingTime(Math.max(0, Math.floor((endTime - Date.now()) / 1000)));
        };

        updateCountdown();
        window.setInterval(updateCountdown, 1000);
    });
</script>
@endsection
