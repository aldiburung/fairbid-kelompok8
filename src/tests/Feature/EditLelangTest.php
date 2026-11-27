<?php

namespace Tests\Feature;

use App\Models\Auction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MembuatDataFairBid;
use Tests\TestCase;

class EditLelangTest extends TestCase
{
    use MembuatDataFairBid;
    use RefreshDatabase;

    public function test_penjual_lain_tidak_boleh_mengubah_lelang(): void
    {
        $penjual = $this->buatUser();
        $penggunaLain = $this->buatUser();
        $lelang = $this->buatLelang($penjual, ['status' => 'DRAFT']);

        $this->actingAs($penggunaLain)
            ->get(route('auctions.edit', $lelang))
            ->assertForbidden();
    }

    public function test_mode_penuh_memperbarui_harga_awal_dan_nilai_ujl(): void
    {
        Storage::fake('public');
        $penjual = $this->buatUser();
        $lelang = $this->buatLelang($penjual, ['status' => 'DRAFT']);
        $this->tambahkanFoto($lelang, 'auctions/draft.jpg');

        $this->actingAs($penjual)
            ->put(route('auctions.update', $lelang), [
                'judul' => $lelang->judul,
                'category_id' => $lelang->category_id,
                'deskripsi' => 'Deskripsi diperbarui.',
                'model_transaksi' => 'C2C',
                'harga_awal' => 250000,
                'kelipatan_bid' => 10000,
                'harga_limit' => null,
                'durasi_jam' => 24,
                'start_time' => null,
                'aksi' => 'simpan',
            ])
            ->assertRedirect(route('auctions.show', $lelang));

        $this->assertDatabaseHas('auction', [
            'auction_id' => $lelang->auction_id,
            'harga_awal' => 250000,
            'nilai_ujl' => 25000,
        ]);
    }

    public function test_mode_terbatas_mengabaikan_harga_dan_menerima_deskripsi(): void
    {
        Storage::fake('public');
        $penjual = $this->buatUser();
        $lelang = $this->buatLelang($penjual, ['status' => 'AKTIF']);
        $this->tambahkanFoto($lelang, 'auctions/active.jpg');

        $this->actingAs($penjual)
            ->put(route('auctions.update', $lelang), [
                'deskripsi' => 'Deskripsi lelang aktif diperbarui.',
                'harga_awal' => 'bukan angka',
                'category_id' => 'kategori-tidak-valid',
                'aksi' => 'simpan',
            ])
            ->assertRedirect(route('auctions.show', $lelang));

        $this->assertDatabaseHas('auction', [
            'auction_id' => $lelang->auction_id,
            'deskripsi' => 'Deskripsi lelang aktif diperbarui.',
            'harga_awal' => 100000,
        ]);
    }

    public function test_hapus_draf_berhasil_dan_hapus_lelang_aktif_ditolak(): void
    {
        Storage::fake('public');
        $penjual = $this->buatUser();
        $draf = $this->buatLelang($penjual, ['status' => 'DRAFT']);
        $this->tambahkanFoto($draf, 'auctions/to-delete.jpg');
        $aktif = $this->buatLelang($penjual, ['status' => 'AKTIF']);
        $this->tambahkanFoto($aktif, 'auctions/keep.jpg');

        $this->actingAs($penjual)
            ->delete(route('auctions.destroy', $draf))
            ->assertRedirect(route('account.lelang-saya'))
            ->assertSessionHas('success', 'Draf lelang dihapus.');

        $this->assertDatabaseMissing('auction', ['auction_id' => $draf->auction_id]);
        $this->assertDatabaseMissing('auction_media', ['auction_id' => $draf->auction_id]);
        Storage::disk('public')->assertMissing('auctions/to-delete.jpg');

        $this->actingAs($penjual)
            ->delete(route('auctions.destroy', $aktif))
            ->assertRedirect(route('auctions.show', $aktif))
            ->assertSessionHas('error', 'Hanya draf lelang yang dapat dihapus.');

        $this->assertDatabaseHas('auction', ['auction_id' => $aktif->auction_id]);
        Storage::disk('public')->assertExists('auctions/keep.jpg');
    }

    public function test_lelang_selesai_tidak_dapat_diubah(): void
    {
        $penjual = $this->buatUser();
        $lelang = $this->buatLelang($penjual, ['status' => 'SELESAI']);

        $this->actingAs($penjual)
            ->get(route('auctions.edit', $lelang))
            ->assertRedirect(route('auctions.show', $lelang))
            ->assertSessionHas('error', 'Lelang ini tidak dapat diubah.');
    }

    private function tambahkanFoto(Auction $lelang, string $path): void
    {
        Storage::disk('public')->put($path, 'foto uji');
        $lelang->auctionMedia()->create([
            'tipe' => 'FOTO',
            'url' => $path,
            'urutan' => 1,
        ]);
    }
}
