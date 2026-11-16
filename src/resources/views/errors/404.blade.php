@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan - FairBid')

@section('content')
<section class="container py-5">
    <div class="card fairbid-card mx-auto text-center" style="max-width: 640px;">
        <div class="card-body p-5">
            <p class="display-4 fw-bold text-fairbid mb-2">404</p>
            <h1 class="h3 fw-bold">Halaman tidak ditemukan</h1>
            <p class="text-secondary mb-4">Halaman yang Anda cari tidak tersedia atau sudah dipindahkan.</p>
            <a href="{{ route('home') }}" class="btn btn-fairbid">Kembali ke Beranda</a>
        </div>
    </div>
</section>
@endsection
