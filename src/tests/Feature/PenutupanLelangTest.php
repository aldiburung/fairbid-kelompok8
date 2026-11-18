<?php

namespace Tests\Feature;

use App\Models\AuctionDeposit;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembuatDataFairBid;
use Tests\TestCase;

class PenutupanLelangTest extends TestCase
{
    use MembuatDataFairBid;
    use RefreshDatabase;

    public function test_pemenang_transaksi_fee_batas_pelunasan_dan_refund_ujl_ditetapkan(): void
    {
        [$lelang, $penjual, $pemenang, $depositPemenang, $depositKalah] = $this->lelangDenganBid(150000, 200000, true);
        $depositKalahId = $depositKalah->deposit_id;
        $batasAkhir = $lelang->refresh()->end_time->copy();

        app(SettlementService::class)->closeAuction($lelang);

        $lelang->refresh();
        $transaksi = Transaction::query()->where('auction_id', $lelang->auction_id)->firstOrFail();
        $this->assertSame('SELESAI', $lelang->status);
        $this->assertSame((int) $pemenang->user_id, (int) $transaksi->buyer_id);
        $this->assertSame((int) $penjual->user_id, (int) $transaksi->seller_id);
        $this->assertSame(6000.0, (float) $transaksi->fee_amount);
        $this->assertSame(194000.0, (float) $transaksi->net_seller);
        $this->assertTrue($transaksi->batas_pelunasan->equalTo($batasAkhir->addHours(24)));
        $this->assertSame('DITAHAN', $depositPemenang->refresh()->status);
        $depositKalah = AuctionDeposit::find($depositKalahId);
        $this->assertNotNull($depositKalah);
        $this->assertSame('DIKEMBALIKAN', $depositKalah->status);
    }

    public function test_harga_limit_tidak_tercapai_menghasilkan_lelang_tanpa_pemenang(): void
    {
        [$lelang, , , , $depositKalah] = $this->lelangDenganBid(150000, 120000, true);

        app(SettlementService::class)->closeAuction($lelang);

        $this->assertSame('TANPA_PEMENANG', $lelang->refresh()->status);
        $this->assertNull($lelang->winning_bid_id);
        $this->assertDatabaseMissing('transaction', ['auction_id' => $lelang->auction_id]);
        $this->assertSame('DIKEMBALIKAN', $depositKalah->refresh()->status);
    }

    public function test_pelunasan_terlambat_membatalkan_transaksi_dan_memotong_ujl_pemenang(): void
    {
        [$lelang, , , $depositPemenang] = $this->lelangDenganBid(null, 200000);
        $layanan = app(SettlementService::class);
        $layanan->closeAuction($lelang);
        $transaksi = Transaction::query()->where('auction_id', $lelang->auction_id)->firstOrFail();
        $this->travelTo($transaksi->batas_pelunasan->copy()->addSecond());

        $layanan->expireUnpaid($transaksi);

        $this->assertSame('BATAL', $transaksi->refresh()->status);
        $this->assertSame('DIPOTONG', $depositPemenang->refresh()->status);
        $this->assertDatabaseHas('payment', [
            'transaction_id' => $transaksi->transaction_id,
            'jenis' => 'POTONG_UJL',
            'status' => 'BERHASIL',
        ]);
    }

    public function test_pelunasan_lalu_pencairan_menyelesaikan_transaksi_satu_kali(): void
    {
        [$lelang, , $pemenang, $depositPemenang] = $this->lelangDenganBid(null, 200000);
        $layanan = app(SettlementService::class);
        $layanan->closeAuction($lelang);
        $transaksi = Transaction::query()->where('auction_id', $lelang->auction_id)->firstOrFail();
        Payment::query()->create([
            'user_id' => $pemenang->user_id,
            'deposit_id' => $depositPemenang->deposit_id,
            'transaction_id' => $transaksi->transaction_id,
            'jenis' => 'PELUNASAN',
            'amount' => 190000,
            'gateway_ref' => 'TEST-PAY-' . uniqid(),
            'idempotency_key' => 'TEST-IDEM-' . uniqid(),
            'status' => 'PENDING',
        ]);

        $layanan->payOff($transaksi);
        $layanan->payOff($transaksi);
        $layanan->release($transaksi);
        $layanan->release($transaksi);

        $this->assertSame('SELESAI', $transaksi->refresh()->status);
        $this->assertSame(1, Payment::query()
            ->where('transaction_id', $transaksi->transaction_id)
            ->where('jenis', 'PENCAIRAN')
            ->count());
    }

    public function test_penutupan_lelang_dua_kali_tidak_menggandakan_data(): void
    {
        [$lelang] = $this->lelangDenganBid(150000, 200000, true);
        $layanan = app(SettlementService::class);

        $layanan->closeAuction($lelang);
        $layanan->closeAuction($lelang);

        $this->assertSame(1, Transaction::query()->where('auction_id', $lelang->auction_id)->count());
        $this->assertSame(1, Payment::query()
            ->where('deposit_id', $lelang->auctionDeposits()->where('status', 'DIKEMBALIKAN')->value('deposit_id'))
            ->where('jenis', 'REFUND_UJL')
            ->count());
    }

    private function lelangDenganBid(?float $hargaLimit, float $nominalBid, bool $withDepositKalah = false): array
    {
        $penjual = $this->buatUser();
        $pemenang = $this->buatUser();
        $lelang = $this->buatLelang($penjual, [
            'harga_limit' => $hargaLimit,
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDay(),
        ]);
        $depositPemenang = $this->buatDepositDitahan($lelang, $pemenang);
        $this->buatBid($lelang, $pemenang, $depositPemenang, [
            'amount' => $nominalBid,
        ]);

        $depositKalah = null;
        if ($withDepositKalah) {
            $pesertaKalah = $this->buatUser();
            $depositKalah = $this->buatDepositDitahan($lelang, $pesertaKalah);
            $this->buatBid($lelang, $pesertaKalah, $depositKalah, [
                'amount' => $nominalBid - 10000,
            ]);
        }

        return [$lelang, $penjual, $pemenang, $depositPemenang, $depositKalah];
    }
}
