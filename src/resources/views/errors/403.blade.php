@extends('layouts.app')

@section('title', 'Akses Ditolak - FairBid')

@section('content')
<section class="container py-5">
    <div class="card fairbid-card mx-auto text-center" style="max-width: 640px;">
        <div class="card-body p-5">
            <p class="display-4 fw-bold text-fairbid mb-2">403</p>
            <h1 class="h3 fw-bold">Akses ditolak</h1>
            <p class="text-secondary mb-4">Anda tidak memiliki izin untuk membuka halaman ini.</p>
            <a href="{{ route('home') }}" class="btn btn-fairbid">Kembali ke Beranda</a>
        </div>
    </div>
</section>
@endsection
