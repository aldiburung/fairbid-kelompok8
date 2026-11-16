@extends('layouts.app')

@section('title', 'Lupa Kata Sandi - FairBid')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card fairbid-card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold text-fairbid">Pulihkan kata sandi</h1>
                        <p class="text-secondary mb-0">Masukkan email akun FairBid Anda.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('password.email') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                        </div>
                        <button type="submit" class="btn btn-fairbid w-100 fw-semibold">KIRIM TAUTAN</button>
                    </form>

                    <p class="text-center mt-4 mb-0">
                        <a href="{{ route('login') }}" class="text-fairbid text-decoration-none">Kembali ke masuk</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
