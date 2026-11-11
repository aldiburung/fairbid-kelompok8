<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionDeposit;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AuctionController extends Controller
{
    public function index(Request $request): View
    {
        $allowedStatuses = ['AKTIF', 'TERJADWAL'];
        $query = Auction::query()
            ->with(['auctionMedia' => function ($mediaQuery) {
                $mediaQuery->where('tipe', 'FOTO')->orderBy('urutan')->orderBy('media_id');
            }])
            ->withMax(['bids as harga_tertinggi' => function ($bidQuery) {
                $bidQuery->where('status', 'VALID');
            }], 'amount')
            ->whereIn('status', $allowedStatuses);

        if ($request->filled('q')) {
            $query->where('judul', 'like', '%' . $request->query('q') . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->filled('model_transaksi')) {
            $query->where('model_transaksi', $request->query('model_transaksi'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return view('auctions.index', [
            'auctions' => $query->orderBy('end_time')->paginate(9)->withQueryString(),
            'categories' => Category::orderBy('nama_kategori')->get(),
        ]);
    }

    public function show(Request $request, Auction $auction): View
    {
        $auction->load([
            'auctionMedia' => function ($mediaQuery) {
                $mediaQuery->where('tipe', 'FOTO')->orderBy('urutan')->orderBy('media_id');
            },
            'seller.personalProfile',
            'seller.businessProfile',
            'winningBid',
        ]);
        $highestBid = $this->highestValidBid($auction);

        $isSeller = (string) $request->user()?->user_id === (string) $auction->seller_id;
        $isWinner = $auction->winningBid !== null
            && (string) $auction->winningBid->bidder_id === (string) $request->user()?->user_id;
        abort_unless(
            in_array($auction->status, ['AKTIF', 'TERJADWAL', 'SELESAI', 'TANPA_PEMENANG'], true) || $isSeller,
            404,
        );

        $seller = $auction->seller;
        $sellerName = $seller->tipe_akun === 'PERSONAL'
            ? $seller->personalProfile?->nama_lengkap
            : $seller->businessProfile?->nama_usaha;

        if (! $sellerName) {
            [$emailName, $emailDomain] = array_pad(explode('@', $seller->email, 2), 2, '');
            $sellerName = ($emailName !== '' ? mb_substr($emailName, 0, 1) . str_repeat('*', max(1, mb_strlen($emailName) - 1)) : '***')
                . ($emailDomain !== '' ? '@' . $emailDomain : '');
        }

        return view('auctions.show', [
            'auction' => $auction,
            'sellerName' => $sellerName,
            'isSeller' => $isSeller,
            'isWinner' => $isWinner,
            'auctionTransaction' => $isSeller || $isWinner
                ? Transaction::query()->where('auction_id', $auction->auction_id)->first()
                : null,
            'hargaTertinggi' => $highestBid?->amount ?? $auction->harga_awal,
            'nominalMinimum' => $highestBid
                ? round((float) $highestBid->amount + (float) $auction->kelipatan_bid, 2)
                : $auction->harga_awal,
            'bidHistory' => $this->recentValidBids($auction),
            'hasExtendedBid' => $auction->bids()
                ->where('status', 'VALID')
                ->where('extended_auction', true)
                ->exists(),
            'userDeposit' => $request->user()
                ? AuctionDeposit::query()
                    ->where('auction_id', $auction->auction_id)
                    ->where('user_id', $request->user()->user_id)
                    ->first()
                : null,
            'initialSeconds' => max(0, (int) now()->diffInSeconds(
                $auction->status === 'TERJADWAL' ? $auction->start_time : $auction->end_time,
                false,
            )),
        ]);
    }

    public function state(Request $request, Auction $auction): JsonResponse
    {
        $isSeller = (string) $request->user()?->user_id === (string) $auction->seller_id;
        abort_unless(in_array($auction->status, ['AKTIF', 'TERJADWAL'], true) || $isSeller, 404);

        $now = now();
        $status = $auction->status === 'TERJADWAL' && $auction->start_time->lte($now)
            ? 'AKTIF'
            : $auction->status;
        $countdownTarget = $status === 'TERJADWAL' ? $auction->start_time : $auction->end_time;
        $highestBid = $this->highestValidBid($auction);
        $minimumAmount = $highestBid
            ? round((float) $highestBid->amount + (float) $auction->kelipatan_bid, 2)
            : (float) $auction->harga_awal;

        return response()->json([
            'end_time' => $auction->end_time->toIso8601String(),
            'start_time' => $auction->start_time->toIso8601String(),
            'status' => $status,
            'harga_tertinggi' => (float) ($highestBid?->amount ?? $auction->harga_awal),
            'nominal_minimum' => $minimumAmount,
            'jumlah_bid' => $auction->bids()->where('status', 'VALID')->count(),
            'sisa_detik' => max(0, (int) $now->diffInSeconds($countdownTarget, false)),
            'riwayat' => $this->recentValidBids($auction),
            'has_extended_bid' => $auction->bids()
                ->where('status', 'VALID')
                ->where('extended_auction', true)
                ->exists(),
        ]);
    }

    private function highestValidBid(Auction $auction): ?Bid
    {
        return $auction->bids()
            ->where('status', 'VALID')
            ->orderByDesc('amount')
            ->first();
    }

    /**
     * @return array<int, array{sequence_no: int, amount: float, server_timestamp: string, penawar: string, extended_auction: bool}>
     */
    private function recentValidBids(Auction $auction): array
    {
        return $auction->bids()
            ->where('status', 'VALID')
            ->orderByDesc('sequence_no')
            ->limit(10)
            ->get()
            ->map(static function (Bid $bid): array {
                $bidderSuffix = str_pad(substr((string) $bid->bidder_id, -2), 2, '0', STR_PAD_LEFT);

                return [
                    'sequence_no' => (int) $bid->sequence_no,
                    'amount' => (float) $bid->amount,
                    'server_timestamp' => $bid->server_timestamp->format('Y-m-d\TH:i:s.uP'),
                    'penawar' => 'Peserta ***' . $bidderSuffix,
                    'extended_auction' => (bool) $bid->extended_auction,
                ];
            })
            ->all();
    }

    public function create(): View
    {
        return view('auctions.create', [
            'categories' => Category::orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->status_verifikasi !== 'DISETUJUI') {
            return back()
                ->withErrors(['status_verifikasi' => 'Anda harus terverifikasi untuk membuat lelang.'])
                ->withInput();
        }

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'exists:category,category_id'],
            'deskripsi' => ['required', 'string'],
            'model_transaksi' => ['required', Rule::in(['C2C', 'C2B'])],
            'harga_awal' => ['required', 'numeric', 'gt:0'],
            'kelipatan_bid' => ['required', 'numeric', 'gt:0'],
            'harga_limit' => ['nullable', 'numeric', 'gte:harga_awal'],
            'durasi_jam' => ['required', Rule::in([6, 12, 24, 48, 72])],
            'start_time' => ['nullable', 'date', 'after:now'],
            'foto' => ['required', 'array', 'min:1', 'max:5'],
            'foto.*' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'aksi' => ['required', Rule::in(['draft', 'terbitkan'])],
        ], [
            'judul.required' => 'Judul lelang wajib diisi.',
            'judul.string' => 'Judul lelang harus berupa teks.',
            'judul.max' => 'Judul lelang maksimal 200 karakter.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
            'model_transaksi.required' => 'Koridor lelang wajib dipilih.',
            'model_transaksi.in' => 'Koridor lelang tidak valid.',
            'harga_awal.required' => 'Harga awal wajib diisi.',
            'harga_awal.numeric' => 'Harga awal harus berupa angka.',
            'harga_awal.gt' => 'Harga awal harus lebih dari 0.',
            'kelipatan_bid.required' => 'Kelipatan bid wajib diisi.',
            'kelipatan_bid.numeric' => 'Kelipatan bid harus berupa angka.',
            'kelipatan_bid.gt' => 'Kelipatan bid harus lebih dari 0.',
            'harga_limit.numeric' => 'Harga limit harus berupa angka.',
            'harga_limit.gte' => 'Harga limit tidak boleh lebih kecil dari harga awal.',
            'durasi_jam.required' => 'Durasi lelang wajib dipilih.',
            'durasi_jam.in' => 'Durasi lelang harus 6, 12, 24, 48, atau 72 jam.',
            'start_time.date' => 'Format waktu mulai tidak valid.',
            'start_time.after' => 'Waktu mulai harus berada di masa depan.',
            'foto.required' => 'Unggah minimal satu foto lelang.',
            'foto.array' => 'Foto lelang tidak valid.',
            'foto.min' => 'Unggah minimal satu foto lelang.',
            'foto.max' => 'Foto lelang maksimal 5 file.',
            'foto.*.required' => 'Setiap foto lelang wajib diisi.',
            'foto.*.image' => 'Setiap file harus berupa gambar.',
            'foto.*.mimes' => 'Foto lelang harus berformat JPG atau PNG.',
            'foto.*.max' => 'Ukuran setiap foto maksimal 2 MB.',
            'aksi.required' => 'Pilih simpan draf atau terbitkan lelang.',
            'aksi.in' => 'Pilihan penyimpanan tidak valid.',
        ]);

        $category = Category::findOrFail($validated['category_id']);
        if ($request->user()->tipe_akun === 'PERSONAL' && ! (bool) $category->izinkan_perorangan) {
            return back()
                ->withErrors(['category_id' => 'Kategori ini tidak tersedia untuk akun Anda.'])
                ->withInput();
        }

        $storedPaths = [];

        try {
            $auction = DB::transaction(function () use ($request, $validated, &$storedPaths): Auction {
                $startTime = isset($validated['start_time'])
                    ? Carbon::parse($validated['start_time'])
                    : now();
                $isDraft = $validated['aksi'] === 'draft';

                $auction = Auction::create([
                    'seller_id' => $request->user()->user_id,
                    'category_id' => $validated['category_id'],
                    'judul' => $validated['judul'],
                    'deskripsi' => $validated['deskripsi'],
                    'model_transaksi' => $validated['model_transaksi'],
                    'harga_awal' => $validated['harga_awal'],
                    'harga_limit' => $validated['harga_limit'] ?? null,
                    'kelipatan_bid' => $validated['kelipatan_bid'],
                    'nilai_ujl' => round((float) $validated['harga_awal'] * 0.10, 2),
                    'start_time' => $startTime,
                    'end_time' => $startTime->copy()->addHours((int) $validated['durasi_jam']),
                    'status' => $isDraft
                        ? 'DRAFT'
                        : ($startTime->isFuture() ? 'TERJADWAL' : 'AKTIF'),
                ]);

                foreach ($request->file('foto') as $index => $photo) {
                    $storedPath = $photo->store('auctions', 'public');
                    if ($storedPath === false) {
                        throw new RuntimeException('Foto lelang gagal disimpan.');
                    }

                    $storedPaths[] = $storedPath;
                    $auction->auctionMedia()->create([
                        'tipe' => 'FOTO',
                        'url' => $storedPath,
                        'urutan' => $index + 1,
                    ]);
                }

                return $auction;
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        return redirect()->route('auctions.show', $auction)->with(
            'success',
            $validated['aksi'] === 'draft'
                ? 'Draf lelang tersimpan.'
                : 'Lelang berhasil diterbitkan.',
        );
    }
}
