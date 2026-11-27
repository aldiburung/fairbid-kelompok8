@extends('layouts.app')

@section('title', 'Ubah lelang - FairBid')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <div class="card fairbid-card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold text-fairbid">Ubah lelang</h1>
                        <p class="text-secondary mb-0">{{ $auction->judul }}</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <p class="fw-semibold mb-1">Perubahan lelang belum berhasil disimpan:</p>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($mode === 'terbatas')
                        <div class="alert alert-info" role="status">Tidak dapat diubah karena lelang sudah berjalan atau sudah ada peserta.</div>
                    @endif

                    <form action="{{ route('auctions.update', $auction) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @include('auctions._fields', [
                            'auction' => $auction,
                            'readonly' => $mode === 'terbatas',
                        ])

                        <div class="mb-3">
                            <label class="form-label">Foto lelang</label>
                            <div class="row g-3 mb-3">
                                @foreach ($auction->auctionMedia as $media)
                                    <div class="col-6 col-md-4">
                                        <div class="card h-100">
                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->url) }}"
                                                alt="{{ $auction->judul }} - foto {{ $loop->iteration }}"
                                                class="card-img-top rounded-top"
                                                style="height: 150px; object-fit: cover;"
                                            >
                                            <div class="card-body py-2">
                                                <div class="form-check">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="hapus_media[]"
                                                        id="hapus_media_{{ $media->media_id }}"
                                                        value="{{ $media->media_id }}"
                                                    >
                                                    <label class="form-check-label" for="hapus_media_{{ $media->media_id }}">Hapus</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <label for="foto" class="form-label">Unggah foto baru</label>
                            <input type="file" class="form-control" id="foto" name="foto[]" accept=".jpg,.jpeg,.png,image/jpeg,image/png" multiple>
                            <div class="form-text">Jumlah foto setelah perubahan harus 1 sampai 5, JPG atau PNG, maksimal 2 MB per foto.</div>
                        </div>

                        <div class="alert alert-info" role="status">
                            <span class="fw-semibold">UJL peserta (otomatis 10%):</span>
                            <span id="nilai_ujl">Rp{{ number_format((float) $auction->nilai_ujl, 2, ',', '.') }}</span>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" class="btn btn-outline-secondary flex-fill" name="aksi" value="simpan">Simpan perubahan</button>
                            @if ($auction->status === 'DRAFT')
                                <button type="submit" class="btn btn-orange fw-semibold flex-fill" name="aksi" value="terbitkan">Terbitkan</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const hargaAwal = document.getElementById('harga_awal');
    const nilaiUjl = document.getElementById('nilai_ujl');
    const formatRupiah = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
    });

    function updateNilaiUjl() {
        const harga = Number(hargaAwal.value);
        nilaiUjl.textContent = formatRupiah.format(Number.isFinite(harga) && harga > 0 ? Math.round(harga * 10) / 100 : 0);
    }

    hargaAwal.addEventListener('input', updateNilaiUjl);
    updateNilaiUjl();
</script>
@endsection
