<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\Aktivitas;
use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use App\Models\User;
use App\Services\Toko\ImporExcelHasil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImporHasilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    /**
     * Membuat berkas xlsx dari kepala + baris; sel string ditulis sebagai teks, angka sebagai angka.
     *
     * @param  array<int, string>  $kepala
     * @param  array<int, array<int, mixed>>  $baris
     */
    private function berkas(array $kepala, array $baris, string $nama = 'isian.xlsx'): UploadedFile
    {
        $buku = new Spreadsheet;
        $ws = $buku->getActiveSheet();
        foreach (array_merge([$kepala], $baris) as $r => $isi) {
            foreach (array_values($isi) as $c => $nilai) {
                $sel = chr(65 + $c).($r + 1);
                if (is_string($nilai) && str_starts_with($nilai, '=')) {
                    $ws->setCellValue($sel, $nilai);
                } elseif ($nilai !== null) {
                    $ws->setCellValueExplicit($sel, $nilai, is_string($nilai) ? DataType::TYPE_STRING : DataType::TYPE_NUMERIC);
                }
            }
        }

        $jalur = tempnam(sys_get_temp_dir(), 'impor').'.xlsx';
        (new Xlsx($buku))->save($jalur);

        return new UploadedFile($jalur, $nama, null, null, true);
    }

    private const KEPALA_KOPI = ['Tanggal panen', 'Jenis kopi', 'Tempat panen', 'Nama petani/kelompok', 'Jumlah panen (kg)', 'Jumlah limbah (kg)'];

    private function unggah(string $jenis, UploadedFile $berkas): TestResponse
    {
        return $this->post(route('admin.toko.impor.pratinjau', $jenis), ['berkas' => $berkas]);
    }

    public function test_templat_bisa_diunduh_dan_bisa_dibaca_balik(): void
    {
        foreach (['panen-kopi', 'panen-jamur', 'penjualan-jamur'] as $jenis) {
            $respons = $this->get(route('admin.toko.impor.templat', $jenis))->assertOk();
            $this->assertStringContainsString("templat-impor-{$jenis}.xlsx", $respons->headers->get('Content-Disposition'));

            $jalur = tempnam(sys_get_temp_dir(), 'tpl');
            file_put_contents($jalur, $respons->streamedContent());
            $buku = IOFactory::load($jalur);
            @unlink($jalur);

            $this->assertSame(['Data', 'Petunjuk'], $buku->getSheetNames());
            $this->assertNotSame('', (string) $buku->getSheet(0)->getCell('A1')->getValue());
        }
    }

    public function test_templat_kosong_ditolak_dengan_pesan_ramah(): void
    {
        $jalur = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        $buku = $this->app->make(ImporExcelHasil::class)->templat('panen-kopi');
        (new Xlsx($buku))->save($jalur);

        $this->unggah('panen-kopi', new UploadedFile($jalur, 'kosong.xlsx', null, null, true))
            ->assertSessionHasErrors(['berkas' => 'Berkas tidak berisi baris data. Isi data mulai baris ke-2 lembar "Data".']);
    }

    public function test_alur_pratinjau_lalu_simpan_menambah_catatan_kopi(): void
    {
        $berkas = $this->berkas(self::KEPALA_KOPI, [
            ['05-10-2026', ' robusta ', 'Kebun  Krajan', 'KWT   Giri', '100,5', 20.25],
            ['06/10/2026', 'Arabika', 'Giri', null, 50, 10],
        ]);

        $this->unggah('panen-kopi', $berkas)
            ->assertOk()
            ->assertSee('Semua 2 baris benar dan siap disimpan.')
            ->assertSee('100,50')
            ->assertSee('05-10-2026');
        $this->assertSame(0, PanenKopi::query()->count(), 'Pratinjau tidak boleh menyimpan.');

        $this->post(route('admin.toko.impor.simpan', 'panen-kopi'))
            ->assertRedirect(route('admin.toko.panen-kopi.index'))
            ->assertSessionHas('sukses');

        $this->assertSame(2, PanenKopi::query()->count());
        $this->assertDatabaseHas('toko_panen_kopi', ['tanggal_panen' => '2026-10-05 00:00:00', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Kebun Krajan', 'nama_petani' => 'KWT Giri', 'jumlah_kg' => '100.50', 'limbah_kg' => '20.25']);
        $this->assertDatabaseHas('toko_panen_kopi', ['jenis_kopi' => 'Arabika', 'nama_petani' => null]);
        $this->assertSame(1, Aktivitas::query()->where('aksi', 'impor_hasil')->count());
    }

    public function test_tombol_simpan_hanya_berlaku_sekali(): void
    {
        $this->unggah('panen-kopi', $this->berkas(self::KEPALA_KOPI, [['05-10-2026', 'Robusta', 'Krajan', null, 10, 1]]));

        $this->post(route('admin.toko.impor.simpan', 'panen-kopi'));
        $this->post(route('admin.toko.impor.simpan', 'panen-kopi'))
            ->assertRedirect(route('admin.toko.impor.show', 'panen-kopi'))
            ->assertSessionHas('galat');

        $this->assertSame(1, PanenKopi::query()->count());
    }

    public function test_simpan_tanpa_pratinjau_tidak_menyimpan_apa_pun(): void
    {
        $this->post(route('admin.toko.impor.simpan', 'panen-jamur'))->assertSessionHas('galat');

        $this->assertSame(0, PanenJamur::query()->count());
    }

    public function test_satu_baris_salah_membatalkan_semua_dan_menyebut_nomor_barisnya(): void
    {
        $berkas = $this->berkas(self::KEPALA_KOPI, [
            ['05-10-2026', 'Robusta', 'Krajan', null, 10, 1],
            ['bukan tanggal', 'Robusta', 'Krajan', null, 10, 1],
            ['05-10-2026', 'Robusta', 'Krajan', null, 'abc', 1],
            ['05-10-2026', 'Robusta', 'Krajan', null, '10,123', 1],
        ]);

        $this->unggah('panen-kopi', $berkas)
            ->assertOk()
            ->assertSee('Belum bisa disimpan: 3 dari 4 baris perlu diperbaiki.')
            ->assertSee('Baris 3 di Excel')
            ->assertSee('Baris 4 di Excel')
            ->assertSee('Baris 5 di Excel')
            ->assertDontSee('Baris 2 di Excel')
            ->assertDontSee('Simpan 1 catatan');

        $this->post(route('admin.toko.impor.simpan', 'panen-kopi'))->assertSessionHas('galat');
        $this->assertSame(0, PanenKopi::query()->count());
    }

    public function test_tanggal_masa_depan_ditolak_seperti_di_form(): void
    {
        $depan = now()->addDays(3)->format('d-m-Y');

        $this->unggah('panen-kopi', $this->berkas(self::KEPALA_KOPI, [[$depan, 'Robusta', 'Krajan', null, 10, 1]]))
            ->assertOk()
            ->assertSee('Belum bisa disimpan');
    }

    public function test_tanggal_bernomor_urut_excel_dibaca_benar(): void
    {
        // 46300 = 05-10-2026 pada penanggalan Excel.
        $this->unggah('panen-kopi', $this->berkas(self::KEPALA_KOPI, [[46300, 'Robusta', 'Krajan', null, 10, 1]]))
            ->assertOk()
            ->assertSee('05-10-2026');
    }

    public function test_rumus_ditolak_dan_tidak_dijalankan(): void
    {
        $this->unggah('panen-kopi', $this->berkas(self::KEPALA_KOPI, [['05-10-2026', 'Robusta', 'Krajan', null, '=1+9', 1]]))
            ->assertOk()
            ->assertSee('berisi rumus')
            ->assertSee('Belum bisa disimpan');
    }

    public function test_isian_html_ditampilkan_sebagai_teks_biasa(): void
    {
        $this->unggah('panen-kopi', $this->berkas(self::KEPALA_KOPI, [['05-10-2026', 'Robusta', 'Krajan', '<script>alert(1)</script>', 10, 1]]))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_berkas_dari_jenis_lain_ditolak(): void
    {
        $berkas = $this->berkas(['Tanggal panen', 'Media tanam', 'Jumlah baglog', 'Jumlah panen (kg)', 'Biaya baglog (Rp)'], [['05-10-2026', 'Dengan cangkang kopi', 10, 5, null]]);

        $this->unggah('panen-kopi', $berkas)
            ->assertSessionHasErrors('berkas');
        $this->assertStringContainsString('Kolom yang tidak ditemukan', session('errors')->first('berkas'));
    }

    public function test_urutan_kolom_boleh_berbeda_selama_judulnya_sama(): void
    {
        $kepala = ['Jumlah limbah (kg)', 'Jumlah panen (kg)', 'Nama petani/kelompok', 'Tempat panen', 'Jenis kopi', 'Tanggal panen'];

        $this->unggah('panen-kopi', $this->berkas($kepala, [[1, 10, null, 'Krajan', 'Robusta', '05-10-2026']]))
            ->assertOk()
            ->assertSee('Semua 1 baris benar');
    }

    public function test_baris_kosong_dilewati(): void
    {
        $this->unggah('panen-kopi', $this->berkas(self::KEPALA_KOPI, [
            ['05-10-2026', 'Robusta', 'Krajan', null, 10, 1],
            [null, null, null, null, null, null],
            ['06-10-2026', 'Robusta', 'Krajan', null, 10, 1],
        ]))
            ->assertOk()
            ->assertSee('Semua 2 baris benar');
    }

    public function test_panen_jamur_membaca_media_dan_biaya(): void
    {
        $kepala = ['Tanggal panen', 'Media tanam', 'Jumlah baglog', 'Jumlah panen (kg)', 'Biaya baglog (Rp)'];
        $this->unggah('panen-jamur', $this->berkas($kepala, [
            ['05-10-2026', 'Dengan cangkang kopi', 40, '12,5', 'Rp 300.000'],
            ['06-10-2026', 'tanpa CANGKANG kopi', '25', 8, null],
        ]))->assertOk()->assertSee('Semua 2 baris benar');

        $this->post(route('admin.toko.impor.simpan', 'panen-jamur'))->assertRedirect(route('admin.toko.panen-jamur.index'));

        $this->assertDatabaseHas('toko_panen_jamur', ['dengan_cangkang_kopi' => 1, 'jumlah_baglog' => 40, 'jumlah_kg' => '12.50', 'biaya_baglog_rupiah' => 300000]);
        $this->assertDatabaseHas('toko_panen_jamur', ['dengan_cangkang_kopi' => 0, 'jumlah_baglog' => 25, 'biaya_baglog_rupiah' => null]);
    }

    public function test_media_tanam_yang_tidak_dikenal_diberi_pesan_jelas(): void
    {
        $kepala = ['Tanggal panen', 'Media tanam', 'Jumlah baglog', 'Jumlah panen (kg)', 'Biaya baglog (Rp)'];

        $this->unggah('panen-jamur', $this->berkas($kepala, [['05-10-2026', 'Campur', 40, 5, null]]))
            ->assertOk()
            ->assertSee('harus ditulis &quot;Dengan cangkang kopi&quot; atau &quot;Tanpa cangkang kopi&quot;', false);
    }

    public function test_penjualan_jamur_bisa_diimpor(): void
    {
        $kepala = ['Tanggal penjualan', 'Media tanam', 'Jumlah terjual (kg)', 'Uang diterima (Rp)'];
        $this->unggah('penjualan-jamur', $this->berkas($kepala, [['06-10-2026', 'Tanpa cangkang kopi', 8, 240000]]))->assertOk();
        $this->post(route('admin.toko.impor.simpan', 'penjualan-jamur'))->assertRedirect(route('admin.toko.penjualan-jamur.index'));

        $this->assertSame(1, PenjualanJamur::query()->where('total_rupiah', 240000)->where('dengan_cangkang_kopi', 0)->count());
    }

    public function test_berkas_bukan_xlsx_ditolak(): void
    {
        $this->unggah('panen-kopi', UploadedFile::fake()->createWithContent('data.csv', "a,b\n1,2"))
            ->assertSessionHasErrors('berkas');

        $this->unggah('panen-kopi', UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan berkas excel'))
            ->assertSessionHasErrors('berkas');
    }

    public function test_batas_jumlah_baris(): void
    {
        $baris = array_fill(0, 501, ['05-10-2026', 'Robusta', 'Krajan', null, 10, 1]);

        $this->unggah('panen-kopi', $this->berkas(self::KEPALA_KOPI, $baris))
            ->assertSessionHasErrors('berkas');
        $this->assertStringContainsString('lebih dari 500 baris', session('errors')->first('berkas'));
    }

    public function test_jenis_tidak_dikenal_tidak_ada(): void
    {
        $this->get('/admin/toko/impor/produk')->assertNotFound();
    }

    public function test_halaman_impor_dan_tombolnya_ada_di_halaman_daftar(): void
    {
        foreach (['panen-kopi', 'panen-jamur', 'penjualan-jamur'] as $jenis) {
            $this->get(route("admin.toko.{$jenis}.index"))->assertSee(route('admin.toko.impor.show', $jenis), false);
            $this->get(route('admin.toko.impor.show', $jenis))->assertOk()->assertSee('Unduh templat Excel');
        }
    }
}
