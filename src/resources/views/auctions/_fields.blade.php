@php
    $auction = $auction ?? null;
    $readonly = $readonly ?? false;
@endphp

<div class="mb-3">
    <label for="judul" class="form-label">Judul lelang</label>
    <input type="text" class="form-control" id="judul" name="judul" value="{{ old('judul', $auction?->judul) }}" maxlength="200" required @if ($readonly) readonly @endif>
</div>

<div class="mb-3">
    <label for="category_id" class="form-label">Kategori</label>
    <select class="form-select" id="category_id" name="category_id" required @disabled($readonly)>
        <option value="">Pilih kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category->category_id }}" @selected((string) old('category_id', $auction?->category_id) === (string) $category->category_id)>
                {{ $category->nama_kategori }}
            </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label for="deskripsi" class="form-label">Deskripsi</label>
    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required>{{ old('deskripsi', $auction?->deskripsi) }}</textarea>
</div>

<div class="mb-3">
    <label for="model_transaksi" class="form-label">Koridor (model transaksi)</label>
    <select class="form-select" id="model_transaksi" name="model_transaksi" required @disabled($readonly)>
        <option value="C2C" @selected(old('model_transaksi', $auction?->model_transaksi ?? 'C2C') === 'C2C')>C2C</option>
        <option value="C2B" @selected(old('model_transaksi', $auction?->model_transaksi) === 'C2B')>C2B</option>
    </select>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="harga_awal" class="form-label">Harga awal</label>
        <input type="number" class="form-control" id="harga_awal" name="harga_awal" value="{{ old('harga_awal', $auction?->harga_awal) }}" min="0.01" step="0.01" required @if ($readonly) readonly @endif>
    </div>
    <div class="col-md-6 mb-3">
        <label for="kelipatan_bid" class="form-label">Kelipatan bid</label>
        <input type="number" class="form-control" id="kelipatan_bid" name="kelipatan_bid" value="{{ old('kelipatan_bid', $auction?->kelipatan_bid) }}" min="0.01" step="0.01" required @if ($readonly) readonly @endif>
    </div>
</div>

<div class="mb-3">
    <label for="harga_limit" class="form-label">Harga limit <span class="text-secondary">(opsional)</span></label>
    <input type="number" class="form-control" id="harga_limit" name="harga_limit" value="{{ old('harga_limit', $auction?->harga_limit) }}" min="0" step="0.01" @if ($readonly) readonly @endif>
</div>

<div class="mb-3">
    <label for="durasi_jam" class="form-label">Durasi lelang</label>
    <select class="form-select" id="durasi_jam" name="durasi_jam" required @disabled($readonly)>
        @php
            $durationValue = $auction
                ? (int) $auction->start_time->diffInHours($auction->end_time)
                : '24';
        @endphp
        @foreach ([6, 12, 24, 48, 72] as $duration)
            <option value="{{ $duration }}" @selected((string) old('durasi_jam', $durationValue) === (string) $duration)>{{ $duration }} jam</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label for="start_time" class="form-label">Waktu mulai <span class="text-secondary">(opsional)</span></label>
    @php
        $startTimeValue = $auction && $auction->start_time->isFuture()
            ? $auction->start_time->format('Y-m-d\TH:i')
            : '';
    @endphp
    <input type="datetime-local" class="form-control" id="start_time" name="start_time" value="{{ old('start_time', $startTimeValue) }}" @if ($readonly) readonly @endif>
    <div class="form-text">Kosongkan untuk memulai lelang sekarang.</div>
</div>
