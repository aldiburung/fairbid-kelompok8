@extends('layouts.app')

@section('title', 'Daftar - FairBid')

@section('content')
<div class="container py-2">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7 col-xl-6">
            <div class="card fairbid-card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold text-fairbid">Buat akun FairBid</h1>
                        <p class="text-secondary mb-0">Daftar untuk mulai mengikuti lelang.</p>
                    </div>

                    <form enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="tipe_akun" class="form-label">Tipe akun</label>
                            <select class="form-select" id="tipe_akun" name="tipe_akun" required>
                                <option value="PERSONAL" selected>Personal</option>
                                <option value="BISNIS">Bisnis</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" autocomplete="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="no_hp" class="form-label">Nomor HP</label>
                            <input type="tel" class="form-control" id="no_hp" name="no_hp" autocomplete="tel" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Kata sandi</label>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                        </div>

                        <div class="mb-3">
                            <label for="foto_ktp" class="form-label">Unggah foto KTP</label>
                            <input type="file" class="form-control" id="foto_ktp" name="foto_ktp" accept="image/*" required>
                            <div class="form-text">Pilih file gambar KTP yang jelas.</div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="syarat" name="syarat" required>
                            <label class="form-check-label" for="syarat">
                                Saya menyetujui syarat dan ketentuan FairBid.
                            </label>
                        </div>

                        <button type="button" class="btn btn-orange w-100 fw-semibold">DAFTAR</button>
                    </form>

                    <p class="text-center text-secondary mt-4 mb-0">
                        Sudah punya akun? <a href="{{ route('login') }}" class="text-fairbid fw-semibold text-decoration-none">Masuk</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
