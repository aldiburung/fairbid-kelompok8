@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi - FairBid')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card fairbid-card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold text-fairbid">Atur ulang kata sandi</h1>
                        <p class="text-secondary mb-0">Buat kata sandi baru untuk akun Anda.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('password.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="token" value="{{ old('token', $token) }}">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Kata sandi baru</label>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Konfirmasi kata sandi</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                        </div>
                        <button type="submit" class="btn btn-fairbid w-100 fw-semibold">SIMPAN KATA SANDI</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
