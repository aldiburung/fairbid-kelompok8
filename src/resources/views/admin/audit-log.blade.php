@extends('layouts.app')

@section('title', 'Audit Log - FairBid')

@section('content')
    <section class="container py-5">
        <div class="mb-4">
            <h1 class="h2 text-fairbid fw-bold mb-1">Audit Log</h1>
            <p class="text-secondary mb-0">Riwayat tindakan penting di FairBid.</p>
        </div>

        <form method="GET" action="{{ route('admin.audit-log.index') }}" class="fairbid-card p-3 p-md-4 mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="filter-aksi" class="form-label">Aksi</label>
                    <input
                        id="filter-aksi"
                        type="text"
                        name="aksi"
                        value="{{ request('aksi') }}"
                        class="form-control"
                        placeholder="Contoh: LELANG_DIBUAT"
                    >
                </div>
                <div class="col-md-5">
                    <label for="filter-entity-type" class="form-label">Jenis entitas</label>
                    <input
                        id="filter-entity-type"
                        type="text"
                        name="entity_type"
                        value="{{ request('entity_type') }}"
                        class="form-control"
                        placeholder="Contoh: auction"
                    >
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-fairbid">Filter</button>
                    <a href="{{ route('admin.audit-log.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>

        @if ($auditLogs->isEmpty())
            <div class="fairbid-card p-4 text-secondary">Belum ada catatan audit yang cocok.</div>
        @else
            <div class="fairbid-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Waktu</th>
                                <th scope="col">Pelaku</th>
                                <th scope="col">Aksi</th>
                                <th scope="col">Entitas</th>
                                <th scope="col">Ringkasan perubahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($auditLogs as $auditLog)
                                <tr>
                                    <td class="text-nowrap">{{ $auditLog->created_at->format('d M Y H:i:s') }}</td>
                                    <td>{{ $auditLog->actor->email }}</td>
                                    <td><span class="badge text-bg-primary">{{ $auditLog->aksi }}</span></td>
                                    <td class="text-nowrap">{{ $auditLog->entity_type }} #{{ $auditLog->entity_id }}</td>
                                    <td>
                                        <div class="small">
                                            <strong>Sebelum:</strong>
                                            <code class="text-break">{{ json_encode($auditLog->nilai_lama, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-' }}</code>
                                        </div>
                                        <div class="small">
                                            <strong>Sesudah:</strong>
                                            <code class="text-break">{{ json_encode($auditLog->nilai_baru, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-' }}</code>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $auditLogs->links('pagination::bootstrap-5') }}</div>
            </div>
        @endif
    </section>
@endsection
