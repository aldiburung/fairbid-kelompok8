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

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <p class="fw-semibold mb-1">Pendaftaran belum berhasil:</p>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('register.submit') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="tipe_akun" class="form-label">Tipe akun</label>
                            <select class="form-select" id="tipe_akun" name="tipe_akun" required>
                                <option value="PERSONAL" @selected(old('tipe_akun', 'PERSONAL') === 'PERSONAL')>Personal</option>
                                <option value="BISNIS" @selected(old('tipe_akun') === 'BISNIS')>Bisnis</option>
                            </select>
                        </div>

                        <div id="personalFields" data-account-fields="PERSONAL">
                            <div class="mb-3">
                                <label for="nama_lengkap" class="form-label">Nama lengkap</label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap') }}" autocomplete="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="nik" class="form-label">NIK</label>
                                <input type="text" class="form-control" id="nik" name="nik" value="{{ old('nik') }}" inputmode="numeric" minlength="16" maxlength="16" required>
                            </div>
                            <div class="mb-3">
                                <label for="tgl_lahir" class="form-label">Tanggal lahir</label>
                                <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" value="{{ old('tgl_lahir') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="alamat_personal" class="form-label">Alamat</label>
                                <textarea class="form-control" id="alamat_personal" name="alamat" rows="3" required>{{ old('alamat') }}</textarea>
                            </div>
                        </div>

                        <div id="businessFields" data-account-fields="BISNIS" hidden>
                            <div class="mb-3">
                                <label for="nama_usaha" class="form-label">Nama usaha</label>
                                <input type="text" class="form-control" id="nama_usaha" name="nama_usaha" value="{{ old('nama_usaha') }}" required disabled>
                            </div>
                            <div class="mb-3">
                                <label for="nib" class="form-label">NIB</label>
                                <input type="text" class="form-control" id="nib" name="nib" value="{{ old('nib') }}" required disabled>
                            </div>
                            <div class="mb-3">
                                <label for="npwp" class="form-label">NPWP</label>
                                <input type="text" class="form-control" id="npwp" name="npwp" value="{{ old('npwp') }}" required disabled>
                            </div>
                            <div class="mb-3">
                                <label for="alamat_bisnis" class="form-label">Alamat usaha</label>
                                <textarea class="form-control" id="alamat_bisnis" name="alamat" rows="3" required disabled>{{ old('alamat') }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="nama_penanggung_jawab" class="form-label">Nama penanggung jawab</label>
                                <input type="text" class="form-control" id="nama_penanggung_jawab" name="nama_penanggung_jawab" value="{{ old('nama_penanggung_jawab') }}" required disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="no_hp" class="form-label">Nomor HP</label>
                            <input type="tel" class="form-control" id="no_hp" name="no_hp" value="{{ old('no_hp') }}" inputmode="numeric" autocomplete="tel" required>
                        </div>

                        <div class="mb-3">
    <label for="password" class="form-label">Kata sandi</label>
    <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
</div>

<div class="mb-3">
    <label for="password_confirmation" class="form-label">Konfirmasi kata sandi</label>
    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
</div>

                        <div class="mb-3">
                            <label for="foto_ktp" class="form-label">Unggah foto KTP</label>
                            <input type="file" class="form-control" id="foto_ktp" name="foto_ktp" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                            <div class="form-text">Pilih file gambar KTP yang jelas.</div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="syarat" name="syarat" value="1" @checked(old('syarat')) required>
                            <label class="form-check-label" for="syarat">
                                Saya menyetujui syarat dan ketentuan FairBid.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-orange w-100 fw-semibold">DAFTAR</button>
                    </form>

                    <p class="text-center text-secondary mt-4 mb-0">
                        Sudah punya akun? <a href="{{ route('login') }}" class="text-fairbid fw-semibold text-decoration-none">Masuk</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const accountType = document.getElementById('tipe_akun');
    const accountFieldGroups = document.querySelectorAll('[data-account-fields]');

    function updateAccountFields() {
        accountFieldGroups.forEach((group) => {
            const isVisible = group.dataset.accountFields === accountType.value;
            group.hidden = !isVisible;
            group.querySelectorAll('input, textarea, select').forEach((field) => {
                field.disabled = !isVisible;
            });
        });
    }

    accountType.addEventListener('change', updateAccountFields);
    updateAccountFields();
</script>
@endsection
