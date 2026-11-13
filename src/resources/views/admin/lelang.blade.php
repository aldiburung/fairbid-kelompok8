@extends('layouts.app')

@section('title', 'Moderasi Lelang - FairBid')

@section('content')
    <section class="container py-5">
        <div class="mb-4">
            <h1 class="h2 text-fairbid fw-bold mb-1">Moderasi Lelang</h1>
            <p class="text-secondary mb-0">Tinjau dan turunkan lelang yang sedang berjalan atau terjadwal.</p>
        </div>

        @if ($errors->has('alasan') || $errors->has('auction'))
            <div class="alert alert-danger">
                {{ $errors->first('alasan') ?: $errors->first('auction') }}
            </div>
        @endif

        @if ($auctions->isEmpty())
            <div class="fairbid-card p-4 text-secondary">
                Tidak ada lelang aktif atau terjadwal untuk dimoderasi.
            </div>
        @else
            <div class="fairbid-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Lelang</th>
                                <th scope="col">Penjual</th>
                                <th scope="col">Status</th>
                                <th scope="col">Jumlah bid</th>
                                <th scope="col">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($auctions as $auction)
                                @php
                                    $seller = $auction->seller;
                                    $sellerName = $seller->tipe_akun === 'PERSONAL'
                                        ? $seller->personalProfile?->nama_lengkap
                                        : $seller->businessProfile?->nama_usaha;
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('auctions.show', $auction) }}" class="fw-semibold text-decoration-none">
                                            {{ $auction->judul }}
                                        </a>
                                    </td>
                                    <td>{{ $sellerName ?: $seller->email }}</td>
                                    <td>
                                        <span class="badge {{ $auction->status === 'AKTIF' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $auction->status === 'AKTIF' ? 'Aktif' : 'Terjadwal' }}
                                        </span>
                                    </td>
                                    <td>{{ $auction->jumlah_bid }}</td>
                                    <td>
                                        <form action="{{ route('admin.lelang.turunkan', $auction) }}" method="POST">
                                            @csrf
                                            <label for="alasan-{{ $auction->auction_id }}" class="visually-hidden">Alasan penurunan</label>
                                            <textarea
                                                id="alasan-{{ $auction->auction_id }}"
                                                name="alasan"
                                                class="form-control form-control-sm mb-2"
                                                rows="2"
                                                required
                                            >{{ old('alasan') }}</textarea>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Turunkan</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
@endsection
