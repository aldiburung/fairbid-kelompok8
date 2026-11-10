<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionDeposit;
use App\Models\Category;
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
        ]);

        $isSeller = (string) $request->user()?->user_id === (string) $auction->seller_id;
        abort_unless(
            in_array($auction->status, ['AKTIF', 'TERJADWAL'], true) || $isSeller,
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
            'userDeposit' => $request->user()
                ? AuctionDeposit::query()
                    ->where('auction_id', $auction->auction_id)
                    ->where('user_id', $request->user()->user_id)
                    ->first()
                : null,
            'initialSeconds' => max(0, (int) now()->diffInSeconds($auction->end_time, false)),
        ]);
    }

    public function state(Request $request, Auction $auction): JsonResponse
    {
        $isSeller = (string) $request->user()?->user_id === (string) $auction->seller_id;
        abort_unless(in_array($auction->status, ['AKTIF', 'TERJADWAL'], true) || $isSeller, 404);

        $now = now();

        return response()->json([
            'end_time' => $auction->end_time->toIso8601String(),
            'status' => $auction->status,
            // Harga tertinggi akan diambil dari tabel bid pada tahap berikutnya.
            'harga_tertinggi' => (float) $auction->harga_awal,
            'sisa_detik' => max(0, (int) $now->diffInSeconds($auction->end_time, false)),
        ]);
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
