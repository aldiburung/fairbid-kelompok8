@extends('layouts.app')

@section('title', 'Ajukan Ulang Verifikasi - FairBid')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card fairbid-card">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 fw-bold text-fairbid mb-3">Ajukan ulang verifikasi</h1>
                    <p class="text-secondary">Unggah foto KTP yang jelas. Format JPG atau PNG, maksimal 2 MB.</p>

                    @if ($latestVerification?->alasan_penolakan)
                        <div class="alert alert-danger" role="alert">
                            Alasan penolakan: {{ $latestVerification->alasan_penolakan }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('account.verification.resubmit') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label for="foto_ktp" class="form-label">Foto KTP</label>
                            <input type="file" class="form-control" id="foto_ktp" name="foto_ktp" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                        </div>
                        <button type="submit" class="btn btn-fairbid w-100 fw-semibold">AJUKAN DOKUMEN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
