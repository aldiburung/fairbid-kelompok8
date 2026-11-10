@extends('layouts.app')

@section('title', 'Verifikasi Pengguna - FairBid')

@section('content')
    <section class="container py-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <h1 class="h2 text-fairbid fw-bold mb-1">Verifikasi Pengguna</h1>
                <p class="text-secondary mb-0">Tinjau dokumen yang menunggu verifikasi.</p>
            </div>
        </div>

        @if ($verifications->isEmpty())
            <div class="fairbid-card p-4 text-secondary">
                Tidak ada pengajuan yang menunggu.
            </div>
        @else
            <div class="fairbid-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Email</th>
                                <th scope="col">Tipe akun</th>
                                <th scope="col">Jenis dokumen</th>
                                <th scope="col">Berkas</th>
                                <th scope="col">Waktu pengajuan</th>
                                <th scope="col">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($verifications as $verification)
                                <tr>
                                    <td>{{ $verification->user->email }}</td>
                                    <td>{{ $verification->user->tipe_akun }}</td>
                                    <td>{{ $verification->jenis_dokumen }}</td>
                                    <td>
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($verification->file_url) }}"
                                            target="_blank" rel="noopener noreferrer">
                                            Lihat berkas
                                        </a>
                                    </td>
                                    <td>{{ $verification->submitted_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="d-flex flex-column gap-2">
                                            <form action="{{ route('admin.verifikasi.approve', $verification) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success w-100">Setujui</button>
                                            </form>
                                            <form action="{{ route('admin.verifikasi.reject', $verification) }}" method="POST">
                                                @csrf
                                                <label for="alasan-{{ $verification->verification_id }}" class="form-label small mb-1">
                                                    Alasan penolakan
                                                </label>
                                                <textarea
                                                    id="alasan-{{ $verification->verification_id }}"
                                                    name="alasan_penolakan"
                                                    class="form-control form-control-sm @error('alasan_penolakan') is-invalid @enderror"
                                                    rows="2"
                                                    required
                                                >{{ old('alasan_penolakan') }}</textarea>
                                                @error('alasan_penolakan')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100 mt-2">Tolak</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
@endsection
