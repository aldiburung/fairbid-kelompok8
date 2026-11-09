<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\Category;
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

        return redirect()->route('home')->with(
            'success',
            $validated['aksi'] === 'draft'
                ? 'Draf lelang tersimpan.'
                : 'Lelang berhasil diterbitkan.',
        );
    }
}
