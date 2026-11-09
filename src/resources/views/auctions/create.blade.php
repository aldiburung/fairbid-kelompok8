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
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul lelang</label>
                            <input type="text" class="form-control" id="judul" name="judul" value="{{ old('judul') }}" maxlength="200" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Kategori</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->category_id }}" @selected((string) old('category_id') === (string) $category->category_id)>
                                        {{ $category->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required>{{ old('deskripsi') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="model_transaksi" class="form-label">Koridor (model transaksi)</label>
                            <select class="form-select" id="model_transaksi" name="model_transaksi" required>
                                <option value="C2C" @selected(old('model_transaksi', 'C2C') === 'C2C')>C2C</option>
                                <option value="C2B" @selected(old('model_transaksi') === 'C2B')>C2B</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="harga_awal" class="form-label">Harga awal</label>
                                <input type="number" class="form-control" id="harga_awal" name="harga_awal" value="{{ old('harga_awal') }}" min="0.01" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="kelipatan_bid" class="form-label">Kelipatan bid</label>
                                <input type="number" class="form-control" id="kelipatan_bid" name="kelipatan_bid" value="{{ old('kelipatan_bid') }}" min="0.01" step="0.01" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="harga_limit" class="form-label">Harga limit <span class="text-secondary">(opsional)</span></label>
                            <input type="number" class="form-control" id="harga_limit" name="harga_limit" value="{{ old('harga_limit') }}" min="0" step="0.01">
                        </div>

                        <div class="mb-3">
                            <label for="durasi_jam" class="form-label">Durasi lelang</label>
                            <select class="form-select" id="durasi_jam" name="durasi_jam" required>
                                @foreach ([6, 12, 24, 48, 72] as $duration)
                                    <option value="{{ $duration }}" @selected((string) old('durasi_jam', '24') === (string) $duration)>{{ $duration }} jam</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="start_time" class="form-label">Waktu mulai <span class="text-secondary">(opsional)</span></label>
                            <input type="datetime-local" class="form-control" id="start_time" name="start_time" value="{{ old('start_time') }}">
                            <div class="form-text">Kosongkan untuk memulai lelang sekarang.</div>
                        </div>

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
