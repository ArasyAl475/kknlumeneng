<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class EksporExcelTest extends TestCase
{
    use RefreshDatabase;

    private function buka(TestResponse $respons): Spreadsheet
    {
        $berkas = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($berkas, $respons->streamedContent());

        try {
            return IOFactory::load($berkas);
        } finally {
            @unlink($berkas);
        }
    }

    private function isiData(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'nama_petani' => 'Kelompok Tani Giri', 'jumlah_kg' => 100, 'limbah_kg' => 40]);
        PanenKopi::query()->create(['tanggal_panen' => '2026-11-02', 'jenis_kopi' => 'Arabika', 'tempat_panen' => 'Giri', 'jumlah_kg' => 50, 'limbah_kg' => 10]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 30, 'jumlah_baglog' => 10, 'dengan_cangkang_kopi' => true]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-06', 'jumlah_kg' => 12.5, 'total_rupiah' => 500000, 'dengan_cangkang_kopi' => true]);
    }

    public function test_warga_bisa_mengunduh_excel_tanpa_masuk(): void
    {
        $this->isiData();

        $respons = $this->get('/toko/hasil/excel')->assertOk();

        $this->assertStringContainsString('spreadsheetml.sheet', $respons->headers->get('Content-Type'));
        $this->assertStringContainsString('hasil-panen-desa-lumeneng-', $respons->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.xlsx', $respons->headers->get('Content-Disposition'));
    }

    public function test_berkas_punya_semua_lembar_dan_angka_cocok_dengan_halaman(): void
    {
        $this->isiData();

        $buku = $this->buka($this->get('/toko/hasil/excel'));

        $this->assertSame(
            ['Ringkasan', 'Perkembangan', 'Panen Kopi', 'Rekap Kopi', 'Panen Jamur', 'Penjualan Jamur', 'Perbandingan Baglog'],
            $buku->getSheetNames(),
        );

        $ringkasan = $buku->getSheetByName('Ringkasan');
        $this->assertSame('Hasil Panen dan Penjualan Komoditas Desa', $ringkasan->getCell('B1')->getValue());
        $teks = collect($ringkasan->toArray(null, true, false))->flatten()->filter()->all();
        $this->assertContains('Semua waktu', $teks);
        $this->assertContains(500000, $teks);
        $this->assertTrue(in_array(150, $teks, false), 'Total panen kopi 150 kg harus ada di ringkasan.');

        $kopi = $buku->getSheetByName('Panen Kopi');
        $this->assertSame('Tanggal timbang', $kopi->getCell('C4')->getValue());
        $this->assertSame('Robusta', $kopi->getCell('F5')->getValue());
        $this->assertSame('Arabika', $kopi->getCell('F6')->getValue());
        $this->assertSame('Jumlah', $kopi->getCell('D7')->getValue());
        $this->assertEquals(150, $kopi->getCell('G7')->getValue());
        $this->assertEquals(50, $kopi->getCell('H7')->getValue());
        $this->assertEquals(100, $kopi->getCell('I7')->getValue());
        $this->assertSame('Kelompok Tani Giri', $kopi->getCell('D5')->getValue());
        $this->assertSame('–', $kopi->getCell('D6')->getValue());

        $jamur = $buku->getSheetByName('Panen Jamur');
        $this->assertSame('Dengan cangkang kopi', $jamur->getCell('D5')->getValue());
        $this->assertEquals(3, $jamur->getCell('G5')->getValue());
    }

    public function test_filter_halaman_ikut_ke_excel(): void
    {
        $this->isiData();

        $buku = $this->buka($this->get('/toko/hasil/excel?jenis=Arabika&dari=2026-11-01&sampai=2026-11-30'));

        $kopi = $buku->getSheetByName('Panen Kopi');
        $this->assertSame('Arabika', $kopi->getCell('F5')->getValue());
        $this->assertSame('Jumlah', $kopi->getCell('D6')->getValue());
        $this->assertEquals(50, $kopi->getCell('G6')->getValue());
        $this->assertStringContainsString('Arabika', $kopi->getCell('B2')->getValue());
        $this->assertSame('Belum ada catatan panen jamur pada rentang ini.', $buku->getSheetByName('Panen Jamur')->getCell('B4')->getValue());
    }

    public function test_isian_berawalan_sama_dengan_tidak_dijalankan_sebagai_rumus(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => '=1+1', 'tempat_panen' => '@SUM(1)', 'jumlah_kg' => 1, 'limbah_kg' => 0]);

        $kopi = $this->buka($this->get('/toko/hasil/excel'))->getSheetByName('Panen Kopi');

        $this->assertSame('=1+1', $kopi->getCell('F5')->getValue());
        $this->assertFalse($kopi->getCell('F5')->isFormula());
        $this->assertFalse($kopi->getCell('E5')->isFormula());
    }

    public function test_tanpa_data_berkas_tetap_valid_dengan_pesan_kosong(): void
    {
        $buku = $this->buka($this->get('/toko/hasil/excel'));

        $this->assertSame('Belum ada catatan panen kopi pada rentang ini.', $buku->getSheetByName('Panen Kopi')->getCell('B4')->getValue());
        $this->assertSame('Belum ada catatan pada rentang ini.', $buku->getSheetByName('Perkembangan')->getCell('B4')->getValue());
    }

    public function test_excel_tidak_memuat_harga_per_transaksi_atau_biaya_baglog(): void
    {
        $this->isiData();

        $buku = $this->buka($this->get('/toko/hasil/excel'));
        $penjualan = collect($buku->getSheetByName('Penjualan Jamur')->toArray(null, true, false))->flatten()->filter()->all();
        $jamur = collect($buku->getSheetByName('Panen Jamur')->toArray(null, true, false))->flatten()->filter()->all();

        $this->assertNotContains(500000, $penjualan);
        $this->assertStringNotContainsString('Biaya', implode(' ', array_map('strval', $jamur)));
    }

    public function test_filter_tidak_valid_dialihkan_ke_halaman_hasil(): void
    {
        $this->get('/toko/hasil/excel?dari=2026-11-30&sampai=2026-11-01')->assertRedirect(route('toko.hasil'));
    }

    public function test_halaman_hasil_menampilkan_tombol_unduh_dengan_filter(): void
    {
        // Tombol Unduh Excel tampil selama sinkron Sheets mati (lihat SinkronSheetsTest untuk tombol Sheets).
        config(['toko_sheets.aktif' => false]);

        $this->get('/toko/hasil?jenis=Arabika')->assertOk()
            ->assertSee('Unduh Excel')
            ->assertSee(route('toko.hasil.excel', ['jenis' => 'Arabika']), false);
    }
}
