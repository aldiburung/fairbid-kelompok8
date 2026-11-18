<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MembuatDataFairBid;
use Tests\TestCase;

class BuatLelangTest extends TestCase
{
    use MembuatDataFairBid;
    use RefreshDatabase;

    /** FR-B01: hanya akun terverifikasi yang boleh membuat lelang. */
    public function test_akun_belum_terverifikasi_ditolak_membuat_lelang(): void
    {
        $penjual = $this->buatUser(['status_verifikasi' => 'BELUM']);

        $this->actingAs($penjual)
            ->post(route('auctions.store'), [])
            ->assertSessionHasErrors([
                'status_verifikasi' => 'Anda harus terverifikasi untuk membuat lelang.',
            ]);

        $this->assertDatabaseCount('auction', 0);
    }

    /** FR-B02: UJL yang disimpan harus sebesar 10% harga awal. */
    public function test_nilai_ujl_disimpan_sebesar_sepuluh_persen_harga_awal(): void
    {
        Storage::fake('public');
        $penjual = $this->buatUser();
        $kategori = $this->buatKategori();

        $this->actingAs($penjual)
            ->post(route('auctions.store'), $this->dataLelang($kategori->category_id, [
                'harga_awal' => 250000,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('auction', [
            'seller_id' => $penjual->user_id,
            'harga_awal' => 250000,
            'nilai_ujl' => 25000,
        ]);
    }

    /** FR-B02: harga limit tidak boleh lebih rendah daripada harga awal. */
    public function test_harga_limit_di_bawah_harga_awal_ditolak(): void
    {
        Storage::fake('public');
        $penjual = $this->buatUser();
        $kategori = $this->buatKategori();

        $this->actingAs($penjual)
            ->post(route('auctions.store'), $this->dataLelang($kategori->category_id, [
                'harga_limit' => 90000,
            ]))
            ->assertSessionHasErrors([
                'harga_limit' => 'Harga limit tidak boleh lebih kecil dari harga awal.',
            ]);

        $this->assertDatabaseCount('auction', 0);
    }

    private function dataLelang(int|string $categoryId, array $override = []): array
    {
        return array_merge([
            'judul' => 'Barang uji FairBid',
            'category_id' => $categoryId,
            'deskripsi' => 'Deskripsi barang untuk pengujian.',
            'model_transaksi' => 'C2C',
            'harga_awal' => 100000,
            'kelipatan_bid' => 10000,
            'durasi_jam' => 24,
            'foto' => [UploadedFile::fake()->image('barang.jpg')],
            'aksi' => 'terbitkan',
        ], $override);
    }
}
