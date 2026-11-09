@extends('layouts.app')

@section('title', 'FairBid - Lelang Online yang Adil dan Aman')

@section('content')
<section class="bg-fairbid text-white py-5">
    <div class="container py-lg-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge rounded-pill bg-white bg-opacity-10 text-white border border-light border-opacity-25 px-3 py-2 mb-3">Marketplace lelang terpercaya</span>
                <h1 class="display-5 fw-bold lh-sm mb-3">Lelang online yang adil dan aman</h1>
                <p class="lead text-white-50 mb-4">Temukan barang istimewa, ajukan penawaran dengan percaya diri, dan menangkan lelang dengan proses yang transparan.</p>
                <div class="d-flex flex-column flex-sm-row gap-3">
                    <a href="#lelang" class="btn btn-orange btn-lg fw-semibold px-4">Lihat Lelang</a>
                    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg fw-semibold px-4">Mulai Jual</a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="rounded-4 p-5 text-center" style="background: linear-gradient(140deg, rgba(255,255,255,.16), rgba(255,255,255,.04)); border: 1px solid rgba(255,255,255,.2);">
                    <div class="display-1 mb-2" aria-hidden="true">⚖</div>
                    <p class="h5 fw-semibold mb-1">Penawaran yang transparan</p>
                    <p class="text-white-50 mb-0">Semua punya kesempatan yang sama.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="lelang" class="py-5">
    <div class="container py-2">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3 mb-4">
            <div>
                <span class="text-uppercase small fw-bold text-fairbid">Pilih barang favoritmu</span>
                <h2 class="h2 text-fairbid fw-bold mb-0 mt-1">Lelang Sedang Berlangsung</h2>
            </div>
            <a href="{{ route('auctions.index') }}" class="text-fairbid fw-semibold text-decoration-none">Lihat katalog</a>
        </div>

        <div class="row g-4">
            @forelse ($auctions as $auction)
                @php
                    $firstPhoto = $auction->auctionMedia->first();
                @endphp
                <div class="col-sm-6 col-lg-4">
                    <article class="card fairbid-card h-100">
                        @if ($firstPhoto)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($firstPhoto->url) }}" class="card-img-top" alt="{{ $auction->judul }}" style="height: 190px; object-fit: cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light text-secondary" style="height: 190px;">Foto belum tersedia</div>
                        @endif
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge rounded-pill {{ $auction->model_transaksi === 'C2C' ? 'text-bg-primary' : 'text-bg-warning' }}">{{ $auction->model_transaksi }}</span>
                                <span class="small text-secondary">Berakhir {{ $auction->end_time->diffForHumans() }}</span>
                            </div>
                            <h3 class="h5 fw-bold text-fairbid">{{ $auction->judul }}</h3>
                            <div class="mt-auto">
                                <div class="small text-secondary">Harga saat ini</div>
                                <div class="fs-5 fw-bold mb-3">Rp{{ number_format((float) $auction->harga_awal, 0, ',', '.') }}</div>
                                <a href="{{ route('auctions.show', $auction) }}" class="btn btn-outline-primary w-100 fw-semibold">Lihat</a>
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info mb-0" role="status">
                        Belum ada lelang aktif. Jadilah yang pertama menawarkan barang di FairBid.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container py-2">
        <div class="text-center mb-5">
            <span class="text-uppercase small fw-bold text-fairbid">Mudah dan transparan</span>
            <h2 class="h2 text-fairbid fw-bold mt-1">Cara Kerja</h2>
        </div>
        <div class="row g-4">
            @php
                $steps = [
                    ['number' => '01', 'title' => 'Daftar', 'description' => 'Buat akun dan lengkapi verifikasi identitas.'],
                    ['number' => '02', 'title' => 'Bayar UJL', 'description' => 'Bayar uang jaminan lelang untuk ikut menawar.'],
                    ['number' => '03', 'title' => 'Tawar', 'description' => 'Ajukan penawaran terbaik sebelum waktu habis.'],
                    ['number' => '04', 'title' => 'Menang dan Bayar', 'description' => 'Menangkan lelang, lalu selesaikan pembayaran.'],
                ];
            @endphp
            @foreach ($steps as $step)
                <div class="col-6 col-lg-3">
                    <div class="h-100 text-center px-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 fw-bold text-fairbid" style="width: 64px; height: 64px; background: rgba(31, 56, 100, .08);">{{ $step['number'] }}</div>
                        <h3 class="h5 fw-bold text-fairbid">{{ $step['title'] }}</h3>
                        <p class="small text-secondary mb-0">{{ $step['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="fairbid-card bg-fairbid text-white p-4 p-md-5 text-center">
        <h2 class="h2 fw-bold">Siap menemukan penawaran terbaik?</h2>
        <p class="text-white-50 mb-4">Gabung di FairBid dan mulai pengalaman lelang yang lebih mudah dan aman.</p>
        <a href="{{ route('register') }}" class="btn btn-orange btn-lg fw-semibold px-4">Daftar Sekarang</a>
    </div>
</section>
@endsection
