@extends('layouts.app')

@section('title', 'Notifikasi - FairBid')

@section('content')
<section class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-secondary mb-1">Pembaruan akun dan lelang</p>
            <h1 class="h2 fw-bold text-fairbid mb-0">Notifikasi</h1>
        </div>
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-primary">Tandai semua dibaca</button>
        </form>
    </div>

    <div class="card fairbid-card">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @php
                    $notificationClass = $notification->is_read ? 'text-secondary' : 'fw-bold text-dark';
                @endphp
                @if (! $notification->is_read)
                    <form action="{{ route('notifications.read', $notification) }}" method="POST" class="list-group-item p-0">
                        @csrf
                        <button type="submit" class="w-100 text-start border-0 bg-transparent p-4 {{ $notificationClass }}">
                            <span class="d-flex align-items-start gap-2">
                                <span class="text-primary" aria-hidden="true">&bull;</span>
                                <span class="flex-grow-1">
                                    <span class="d-block">{{ $notification->isi }}</span>
                                    <small class="d-block text-secondary mt-2">{{ $notification->created_at->format('d M Y H:i') }}</small>
                                </span>
                            </span>
                        </button>
                    </form>
                @elseif ($notification->auction)
                    <a href="{{ route('auctions.show', $notification->auction) }}" class="list-group-item list-group-item-action p-4 {{ $notificationClass }}">
                        <span class="d-block">{{ $notification->isi }}</span>
                        <small class="d-block text-secondary mt-2">{{ $notification->created_at->format('d M Y H:i') }}</small>
                    </a>
                @else
                    <div class="list-group-item p-4 {{ $notificationClass }}">
                        <span class="d-block">{{ $notification->isi }}</span>
                        <small class="d-block text-secondary mt-2">{{ $notification->created_at->format('d M Y H:i') }}</small>
                    </div>
                @endif
            @empty
                <div class="p-4 text-secondary">Belum ada notifikasi.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
</section>
@endsection
