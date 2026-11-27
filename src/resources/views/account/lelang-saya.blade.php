@extends('layouts.app')

@section('title', 'Lelang Saya - FairBid')

@section('content')
<section class="container py-5">
    <div class="mb-4">
        <span class="text-uppercase small fw-bold text-fairbid">Akun Anda</span>
        <h1 class="h2 text-fairbid fw-bold mb-0 mt-1">Lelang Saya</h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a
                class="nav-link {{ $activeTab === 'dijual' ? 'active' : '' }}"
                href="{{ route('account.lelang-saya', ['tab' => 'dijual']) }}"
                @if ($activeTab === 'dijual') aria-current="page" @endif
            >Dijual</a>
        </li>
        <li class="nav-item" role="presentation">
            <a
                class="nav-link {{ $activeTab === 'diikuti' ? 'active' : '' }}"
                href="{{ route('account.lelang-saya', ['tab' => 'diikuti']) }}"
                @if ($activeTab === 'diikuti') aria-current="page" @endif
            >Diikuti</a>
        </li>
    </ul>

    @if ($activeTab === 'dijual')
        @if ($sellingAuctions->isEmpty())
            <div class="alert alert-info" role="status">Anda belum memiliki lelang.</div>
        @else
            <div class="card fairbid-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Judul</th>
                                <th scope="col">Status</th>
                                <th scope="col">Harga saat ini</th>
                                <th scope="col">Jumlah bid</th>
                                <th scope="col"><span class="visually-hidden">Tautan</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sellingAuctions as $auction)
                                <tr>
                                    <th scope="row" class="text-fairbid">{{ $auction->judul }}</th>
                                    <td>{{ str_replace('_', ' ', $auction->status) }}</td>
                                    <td>Rp{{ number_format((float) ($auction->harga_tertinggi ?? $auction->harga_awal), 0, ',', '.') }}</td>
                                    <td>{{ $auction->jumlah_bid }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('auctions.show', $auction) }}" class="btn btn-sm btn-outline-primary">Lihat lelang</a>
                                        @if ($auction->dapatDiubah())
                                            <a href="{{ route('auctions.edit', $auction) }}" class="btn btn-sm btn-outline-secondary">Ubah</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        @if ($deposits->isEmpty())
            <div class="alert alert-info" role="status">Anda belum mengikuti lelang.</div>
        @else
            <div class="card fairbid-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Lelang</th>
                                <th scope="col">Status UJL</th>
                                <th scope="col">Status lelang</th>
                                <th scope="col">Posisi</th>
                                <th scope="col"><span class="visually-hidden">Tautan</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($deposits as $deposit)
                                <tr>
                                    <th scope="row" class="text-fairbid">{{ $deposit->auction->judul }}</th>
                                    <td>{{ str_replace('_', ' ', $deposit->status) }}</td>
                                    <td>{{ str_replace('_', ' ', $deposit->auction->status) }}</td>
                                    <td>{{ $deposit->posisi }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('auctions.show', $deposit->auction) }}" class="btn btn-sm btn-outline-primary">Lihat lelang</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</section>
@endsection
