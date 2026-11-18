<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatDataFairBid;
use Tests\TestCase;

class PembatalanLelangTest extends TestCase
{
    use MembuatDataFairBid;
    use RefreshDatabase;

    public function test_penjual_tidak_bisa_membatalkan_lelang_yang_sudah_memiliki_bid(): void
    {
        $penjual = $this->buatUser();
        $penawar = $this->buatUser();
        $lelang = $this->buatLelang($penjual);
        $deposit = $this->buatDepositDitahan($lelang, $penawar);
        $this->buatBid($lelang, $penawar, $deposit);

        $this->actingAs($penjual)
            ->post(route('auctions.cancel', $lelang))
            ->assertSessionHasErrors([
                'auction' => 'Lelang tidak dapat dibatalkan karena sudah ada penawaran.',
            ]);

        $this->assertSame('AKTIF', $lelang->refresh()->status);
    }

    public function test_pembatalan_tanpa_bid_mengembalikan_deposit_ujl_ditahan(): void
    {
        $penjual = $this->buatUser();
        $peserta = $this->buatUser();
        $lelang = $this->buatLelang($penjual);
        $deposit = $this->buatDepositDitahan($lelang, $peserta);

        $this->actingAs($penjual)
            ->post(route('auctions.cancel', $lelang))
            ->assertRedirect(route('auctions.show', $lelang));

        $this->assertSame('DIBATALKAN', $lelang->refresh()->status);
        $this->assertSame('DIKEMBALIKAN', $deposit->refresh()->status);
        $this->assertDatabaseHas('payment', [
            'deposit_id' => $deposit->deposit_id,
            'jenis' => 'REFUND_UJL',
            'status' => 'BERHASIL',
        ]);
    }
}
