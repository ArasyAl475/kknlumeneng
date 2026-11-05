<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use App\Services\Toko\RekapHasil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekapHasilTest extends TestCase
{
    use RefreshDatabase;

    private function kopi(string $tanggal, string $jenis, string $tempat, float $panen, float $limbah): void
    {
        PanenKopi::query()->create(['tanggal_panen' => $tanggal, 'jenis_kopi' => $jenis, 'tempat_panen' => $tempat, 'jumlah_kg' => $panen, 'limbah_kg' => $limbah]);
    }

    private function dataContoh(): void
    {
        $this->kopi('2025-12-31', 'Robusta', 'Krajan', 10, 2);
        $this->kopi('2026-10-05', 'Arabika', 'Krajan', 20.5, 4.25);
        $this->kopi('2026-10-05', 'Robusta', 'Sigemplong', 30, 6);
        $this->kopi('2026-11-01', 'Robusta', 'Sigemplong', 40, 8);
    }

    public function test_total_kopi_dan_rinciannya(): void
    {
        $this->dataContoh();

        $hasil = app(RekapHasil::class)->kopi();

        $this->assertSame(100.5, $hasil['panen']);
        $this->assertSame(20.25, $hasil['limbah']);
        $this->assertSame([
            ['label' => 'Arabika', 'panen' => 20.5, 'limbah' => 4.25, 'lebar' => 25.6],
            ['label' => 'Robusta', 'panen' => 80.0, 'limbah' => 16.0, 'lebar' => 100.0],
        ], $hasil['per_jenis']);
        $this->assertSame(20.1, $hasil['persen_limbah']);
        $this->assertSame(['Krajan', 'Sigemplong'], array_column($hasil['per_tempat'], 'label'));
        $this->assertSame([30.5, 70.0], array_column($hasil['per_tempat'], 'panen'));
        $this->assertSame(['2026', '2025'], array_column($hasil['per_tahun'], 'label'));
        $this->assertSame([90.5, 10.0], array_column($hasil['per_tahun'], 'panen'));
        $this->assertSame(['November 2026', 'Oktober 2026', 'Desember 2025'], array_column($hasil['per_bulan'], 'label'));
        $this->assertSame('Senin, 05-10-2026', $hasil['per_hari'][1]['label']);
        $this->assertSame(50.5, $hasil['per_hari'][1]['panen']);
    }

    public function test_jumlah_desimal_tidak_menyisakan_galat_pembulatan(): void
    {
        $this->kopi('2026-10-05', 'Robusta', 'Krajan', 0.1, 0.1);
        $this->kopi('2026-10-05', 'Robusta', 'Krajan', 0.2, 0.2);

        $this->assertSame(0.3, app(RekapHasil::class)->kopi()['panen']);
    }

    public function test_filter_tanggal_mencakup_hari_pertama_dan_terakhir(): void
    {
        $this->dataContoh();
        $rekap = app(RekapHasil::class);

        $this->assertSame(50.5, $rekap->kopi(['dari' => '2026-10-05', 'sampai' => '2026-10-05'])['panen']);
        $this->assertSame(40.0, $rekap->kopi(['dari' => '2026-10-06'])['panen']);
        $this->assertSame(10.0, $rekap->kopi(['sampai' => '2025-12-31'])['panen']);
    }

    public function test_filter_jenis_dan_tempat(): void
    {
        $this->dataContoh();
        $rekap = app(RekapHasil::class);

        $this->assertSame(80.0, $rekap->kopi(['jenis' => 'Robusta'])['panen']);
        $this->assertSame(30.5, $rekap->kopi(['tempat' => 'Krajan'])['panen']);
        $this->assertSame(10.0, $rekap->kopi(['jenis' => 'Robusta', 'tempat' => 'Krajan'])['panen']);
    }

    public function test_rincian_harian_dibatasi_tetapi_total_tetap_utuh(): void
    {
        foreach (range(0, RekapHasil::BATAS_HARIAN) as $mundur) {
            $this->kopi(now()->subDays($mundur)->format('Y-m-d'), 'Robusta', 'Krajan', 1, 0);
        }

        $hasil = app(RekapHasil::class)->kopi();

        $this->assertCount(RekapHasil::BATAS_HARIAN, $hasil['per_hari']);
        $this->assertTrue($hasil['hari_terpotong']);
        $this->assertSame((float) RekapHasil::BATAS_HARIAN + 1, $hasil['panen']);
    }

    public function test_total_jamur_memisahkan_cangkang_kopi(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 5.5, 'dengan_cangkang_kopi' => true]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-06', 'jumlah_kg' => 3, 'dengan_cangkang_kopi' => false]);

        $this->assertSame(['total' => 8.5, 'cangkang' => 5.5, 'persen_cangkang' => 64.7], app(RekapHasil::class)->jamur());
        $this->assertSame(['total' => 3.0, 'cangkang' => 0.0, 'persen_cangkang' => 0.0], app(RekapHasil::class)->jamur(['dari' => '2026-10-06']));
    }

    public function test_total_penjualan_kilogram_dan_rupiah(): void
    {
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-05', 'jumlah_kg' => 2.5, 'total_rupiah' => 62500]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-07', 'jumlah_kg' => 1, 'total_rupiah' => 25000]);

        $this->assertSame(['kg' => 3.5, 'rupiah' => 87500], app(RekapHasil::class)->penjualan());
        $this->assertSame(['kg' => 2.5, 'rupiah' => 62500], app(RekapHasil::class)->penjualan(['sampai' => '2026-10-05']));
    }

    public function test_perbandingan_baglog_dengan_dan_tanpa_cangkang(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 30, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => true]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-06', 'jumlah_kg' => 20, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => false]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-07', 'jumlah_kg' => 10, 'dengan_cangkang_kopi' => true]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-08', 'jumlah_kg' => 12, 'dengan_cangkang_kopi' => true, 'total_rupiah' => 300000]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-08', 'jumlah_kg' => 99, 'total_rupiah' => 1]);

        $hasil = app(RekapHasil::class)->perbandinganJamur();

        $this->assertSame(['label' => 'Dengan cangkang kopi', 'baglog' => 100, 'panen' => 40.0, 'kg_per_baglog' => 0.3, 'terjual_kg' => 12.0], $hasil[0]);
        $this->assertSame(['label' => 'Tanpa cangkang kopi', 'baglog' => 100, 'panen' => 20.0, 'kg_per_baglog' => 0.2, 'terjual_kg' => 0.0], $hasil[1]);
        $this->assertArrayNotHasKey('harga_per_kg', $hasil[0]);
    }

    public function test_keuangan_jamur_menghitung_harga_dan_biaya_baglog(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 30, 'jumlah_baglog' => 100, 'biaya_baglog_rupiah' => 500000, 'dengan_cangkang_kopi' => true]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-06', 'jumlah_kg' => 20, 'jumlah_baglog' => 50, 'dengan_cangkang_kopi' => false]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-08', 'jumlah_kg' => 10, 'dengan_cangkang_kopi' => false, 'total_rupiah' => 450000]);

        $hasil = app(RekapHasil::class)->keuanganJamur();

        $this->assertSame(['label' => 'Dengan cangkang kopi', 'harga_per_kg' => null, 'biaya_per_baglog' => 5000], $hasil[0]);
        $this->assertSame(['label' => 'Tanpa cangkang kopi', 'harga_per_kg' => 45000, 'biaya_per_baglog' => null], $hasil[1]);
    }

    public function test_perbandingan_mengikuti_filter_tanggal(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 30, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => true]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-11-05', 'jumlah_kg' => 5, 'jumlah_baglog' => 10, 'dengan_cangkang_kopi' => true]);

        $hasil = app(RekapHasil::class)->perbandinganJamur(['dari' => '2026-11-01']);

        $this->assertSame(10, $hasil[0]['baglog']);
        $this->assertSame(5.0, $hasil[0]['panen']);
    }

    public function test_data_kosong_menghasilkan_nol(): void
    {
        $this->assertSame(0.0, app(RekapHasil::class)->kopi()['panen']);
        $this->assertSame(0.0, app(RekapHasil::class)->kopi()['persen_limbah']);
        $this->assertSame(['total' => 0.0, 'cangkang' => 0.0, 'persen_cangkang' => 0.0], app(RekapHasil::class)->jamur());
        $this->assertSame(['kg' => 0.0, 'rupiah' => 0], app(RekapHasil::class)->penjualan());
    }

    public function test_pintasan_periode_menandai_pilihan_aktif(): void
    {
        $this->travelTo('2026-11-05 10:00:00');
        $rekap = app(RekapHasil::class);

        $semua = $rekap->periode();
        $this->assertTrue($semua['pilihan'][0]['aktif']);
        $this->assertSame('Semua waktu', $semua['keterangan']);
        $this->assertFalse($semua['lanjutan']);

        $bulan = $rekap->periode(['dari' => '2026-11-01', 'sampai' => '2026-11-30']);
        $this->assertTrue($bulan['pilihan'][2]['aktif']);
        $this->assertSame('Bulan ini', $bulan['keterangan']);
        $this->assertFalse($bulan['lanjutan']);
    }

    public function test_keterangan_periode_untuk_pilihan_sendiri(): void
    {
        $this->travelTo('2026-11-05 10:00:00');
        $rekap = app(RekapHasil::class);

        $hasil = $rekap->periode(['dari' => '2026-10-05', 'sampai' => '2026-10-07', 'jenis' => 'Robusta', 'tempat' => 'Krajan']);
        $this->assertSame('05-10-2026 sampai 07-10-2026 · Robusta · Krajan', $hasil['keterangan']);
        $this->assertTrue($hasil['lanjutan']);
        $this->assertSame(['dari' => '2026-01-01', 'sampai' => '2026-12-31', 'jenis' => 'Robusta', 'tempat' => 'Krajan'], $hasil['pilihan'][1]['kueri']);

        $this->assertSame('Sejak 05-10-2026', $rekap->periode(['dari' => '2026-10-05'])['keterangan']);
        $this->assertSame('Sampai 07-10-2026', $rekap->periode(['sampai' => '2026-10-07'])['keterangan']);
    }
}
