@extends('layouts.app')

@section('title', 'Profil - FairBid')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card fairbid-card mb-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 fw-bold text-fairbid mb-4">Profil akun</h1>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <dl class="row mb-4">
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $user->email }}</dd>
                        <dt class="col-sm-4">Tipe akun</dt>
                        <dd class="col-sm-8">{{ $user->tipe_akun }}</dd>
                        <dt class="col-sm-4">Status verifikasi</dt>
                        <dd class="col-sm-8">{{ $user->status_verifikasi }}</dd>

                        @if ($user->tipe_akun === 'PERSONAL')
                            <dt class="col-sm-4">Nama lengkap</dt>
                            <dd class="col-sm-8">{{ $user->personalProfile?->nama_lengkap }}</dd>
                            <dt class="col-sm-4">NIK</dt>
                            <dd class="col-sm-8">{{ $user->personalProfile?->nik }}</dd>
                            <dt class="col-sm-4">Tanggal lahir</dt>
                            <dd class="col-sm-8">{{ $user->personalProfile?->tgl_lahir }}</dd>
                        @else
                            <dt class="col-sm-4">Nama usaha</dt>
                            <dd class="col-sm-8">{{ $user->businessProfile?->nama_usaha }}</dd>
                            <dt class="col-sm-4">NIB</dt>
                            <dd class="col-sm-8">{{ $user->businessProfile?->nib }}</dd>
                            <dt class="col-sm-4">NPWP</dt>
                            <dd class="col-sm-8">{{ $user->businessProfile?->npwp }}</dd>
                            <dt class="col-sm-4">Penanggung jawab</dt>
                            <dd class="col-sm-8">{{ $user->businessProfile?->nama_penanggung_jawab }}</dd>
                        @endif
                    </dl>

                    @if ($latestVerification?->alasan_penolakan)
                        <div class="alert alert-danger" role="alert">
                            Alasan penolakan: {{ $latestVerification->alasan_penolakan }}
                        </div>
                    @endif

                    <form action="{{ route('account.profile.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="{{ $user->email }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="no_hp" class="form-label">Nomor HP</label>
                            <input type="text" class="form-control" id="no_hp" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" inputmode="numeric" required>
                        </div>
                        <div class="mb-4">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" required>{{ old('alamat', $user->tipe_akun === 'PERSONAL' ? $user->personalProfile?->alamat : $user->businessProfile?->alamat) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-fairbid fw-semibold">SIMPAN PERUBAHAN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
