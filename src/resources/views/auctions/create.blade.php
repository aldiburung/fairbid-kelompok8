@extends('layouts.app')

@section('title', 'Buat lelang - FairBid')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <div class="card fairbid-card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold text-fairbid">Buat lelang</h1>
                        <p class="text-secondary mb-0">Lengkapi informasi barang yang ingin Anda lelang.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <p class="fw-semibold mb-1">Lelang belum berhasil disimpan:</p>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('auctions.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('auctions._fields', ['auction' => null, 'readonly' => false])

                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto lelang</label>
                            <input type="file" class="form-control" id="foto" name="foto[]" accept=".jpg,.jpeg,.png,image/jpeg,image/png" multiple required>
                            <div class="form-text">Unggah 1 sampai 5 foto JPG atau PNG, maksimal 2 MB per foto.</div>
                        </div>

                        <div class="alert alert-info" role="status">
                            <span class="fw-semibold">UJL peserta (otomatis 10%):</span>
                            <span id="nilai_ujl">Rp0,00</span>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" class="btn btn-outline-secondary flex-fill" name="aksi" value="draft">Simpan draf</button>
                            <button type="submit" class="btn btn-orange fw-semibold flex-fill" name="aksi" value="terbitkan">Terbitkan lelang</button>
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
