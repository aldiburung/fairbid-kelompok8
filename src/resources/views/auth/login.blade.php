@extends('layouts.app')

@section('title', 'Masuk - FairBid')

@section('content')
<div class="container py-2">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card fairbid-card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold text-fairbid">Selamat datang kembali</h1>
                        <p class="text-secondary mb-0">Masuk ke akun FairBid Anda.</p>
                    </div>

                    <form>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
                        </div>

                        <div class="mb-2">
                            <label for="password" class="form-label">Kata sandi</label>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                        </div>

                        <div class="text-end mb-4">
                            <a href="#" class="text-fairbid text-decoration-none">Lupa kata sandi?</a>
                        </div>

                        <button type="button" class="btn btn-fairbid w-100 fw-semibold">MASUK</button>
                    </form>

                    <p class="text-center text-secondary mt-4 mb-0">
                        Belum punya akun? <a href="{{ route('register') }}" class="text-fairbid fw-semibold text-decoration-none">Daftar</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
