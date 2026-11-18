<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatDataFairBid;
use Tests\TestCase;

class BidTest extends TestCase
{
    use MembuatDataFairBid;
    use RefreshDatabase;

    /** FR-B06: penjual tidak boleh menjadi penawar pada lelangnya sendiri. */
    public function test_penjual_tidak_bisa_menawar_lelang_sendiri(): void
    {
        $penjual = $this->buatUser();
        $lelang = $this->buatLelang($penjual);
        $deposit = $this->buatDepositDitahan($lelang, $penjual);

        $this->actingAs($penjual)
            ->post(route('bids.store', $lelang), ['amount' => 100000])
            ->assertSessionHasErrors([
                'amount' => 'Anda tidak dapat menawar lelang sendiri.',
            ]);

        $this->assertDatabaseMissing('bid', ['deposit_id' => $deposit->deposit_id]);
    }

    public function test_bid_ditolak_jika_tidak_ada_ujl_berstatus_ditahan(): void
    {
        $penjual = $this->buatUser();
        $penawar = $this->buatUser();
        $lelang = $this->buatLelang($penjual);

        $this->actingAs($penawar)
            ->post(route('bids.store', $lelang), ['amount' => 100000])
            ->assertSessionHasErrors(['amount' => 'UJL Anda belum dibayar.']);
    }

    public function test_bid_di_bawah_bid_tertinggi_ditambah_kelipatan_ditolak(): void
    {
        [$lelang, $penawar, $deposit] = $this->lelangDenganBidAwal();
        $this->buatBid($lelang, $penawar, $deposit, ['amount' => 100000]);
        $penawarBerikutnya = $this->buatUser();
        $this->buatDepositDitahan($lelang, $penawarBerikutnya);

        $this->actingAs($penawarBerikutnya)
            ->post(route('bids.store', $lelang), ['amount' => 109999])
            ->assertSessionHasErrors([
                'amount' => 'Nominal minimal Rp 110.000,00.',
            ]);
    }

    /** FR-C01: nomor urut bid harus berlanjut secara berurutan. */
    public function test_bid_valid_disimpan_dengan_nomor_urut_berurutan(): void
    {
        [$lelang, $penawar, $deposit] = $this->lelangDenganBidAwal();
        $this->buatBid($lelang, $penawar, $deposit, ['amount' => 100000]);
        $penawarBerikutnya = $this->buatUser();
        $this->buatDepositDitahan($lelang, $penawarBerikutnya);

        $this->actingAs($penawarBerikutnya)
            ->post(route('bids.store', $lelang), ['amount' => 110000])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bid', [
            'auction_id' => $lelang->auction_id,
            'bidder_id' => $penawarBerikutnya->user_id,
            'amount' => 110000,
            'sequence_no' => 2,
            'status' => 'VALID',
        ]);
    }

    /** FR-C04: bid pada jendela anti-sniping memperpanjang batas akhir. */
    public function test_bid_dalam_dua_menit_terakhir_memperpanjang_lelang(): void
    {
        $penjual = $this->buatUser();
        $penawar = $this->buatUser();
        $lelang = $this->buatLelang($penjual, [
            'end_time' => now()->addMinute(),
        ]);
        $deposit = $this->buatDepositDitahan($lelang, $penawar);
        $batasAwal = $lelang->refresh()->end_time->copy();

        $this->actingAs($penawar)
            ->post(route('bids.store', $lelang), ['amount' => 100000])
            ->assertSessionHasNoErrors();

        $bid = $lelang->bids()->firstOrFail();
        $this->assertTrue($bid->extended_auction);
        $this->assertTrue($lelang->refresh()->end_time->equalTo($batasAwal->addMinutes(2)));
        $this->assertSame((int) $deposit->deposit_id, (int) $bid->deposit_id);
    }

    public function test_bid_di_luar_jendela_anti_sniping_tidak_memperpanjang_lelang(): void
    {
        $penjual = $this->buatUser();
        $penawar = $this->buatUser();
        $lelang = $this->buatLelang($penjual, [
            'end_time' => now()->addMinutes(10),
        ]);
        $batasAwal = $lelang->refresh()->end_time->copy();
        $this->buatDepositDitahan($lelang, $penawar);

        $this->actingAs($penawar)
            ->post(route('bids.store', $lelang), ['amount' => 100000])
            ->assertSessionHasNoErrors();

        $this->assertFalse($lelang->bids()->firstOrFail()->extended_auction);
        $this->assertTrue($lelang->refresh()->end_time->equalTo($batasAwal));
    }

    public function test_bid_pada_lelang_terjadwal_ditolak_dengan_pesan_belum_dimulai(): void
    {
        $penjual = $this->buatUser();
        $penawar = $this->buatUser();
        $lelang = $this->buatLelang($penjual, [
            'status' => 'TERJADWAL',
            'start_time' => now()->addHour(),
        ]);

        $response = $this->actingAs($penawar)
            ->post(route('bids.store', $lelang), ['amount' => 100000]);

        $response->assertSessionHasErrors('amount');
        $this->assertStringContainsString(
            'Lelang belum dimulai',
            session('errors')->get('amount')[0],
        );
    }

    private function lelangDenganBidAwal(): array
    {
        $penjual = $this->buatUser();
        $penawar = $this->buatUser();
        $lelang = $this->buatLelang($penjual);
        $deposit = $this->buatDepositDitahan($lelang, $penawar);

        return [$lelang, $penawar, $deposit];
    }
}
