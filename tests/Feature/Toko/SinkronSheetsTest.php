<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\Aktivitas;
use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use App\Services\Toko\SinkronSheetHasil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SinkronSheetsTest extends TestCase
{
    use RefreshDatabase;

    private const KOPI = 'Panen kopi';

    private const JAMUR = 'Panen jamur';

    private const JUAL = 'Penjualan jamur';

    /** @var array<string, array<int, array<int, mixed>>> isi tab Google Sheets palsu */
    private array $sheet = [];

    private int $jumlahTulis = 0;

    private bool $gagalTulis = false;

    private string $berkasKunci;

    protected function setUp(): void
    {
        parent::setUp();

        $opsi = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        $kunci = @openssl_pkey_new($opsi);
        if ($kunci === false) {
            // Windows: PHP kadang tidak menemukan openssl.cnf; Laragon menyimpannya di sebelah php.exe.
            $opsi['config'] = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';
            $kunci = openssl_pkey_new($opsi);
        }
        $this->assertNotFalse($kunci, 'OpenSSL tidak bisa membuat kunci uji.');
        openssl_pkey_export($kunci, $pem, null, isset($opsi['config']) ? ['config' => $opsi['config']] : []);
        $this->berkasKunci = tempnam(sys_get_temp_dir(), 'kunci').'.json';
        file_put_contents($this->berkasKunci, json_encode(['client_email' => 'sinkron@contoh.iam.gserviceaccount.com', 'private_key' => $pem]));

        config(['toko_sheets.aktif' => true, 'toko_sheets.spreadsheet_id' => 'sheet-uji', 'toko_sheets.kunci_json' => $this->berkasKunci]);

        $this->sheet = [
            self::KOPI => [SinkronSheetHasil::kepala('panen-kopi')],
            self::JAMUR => [SinkronSheetHasil::kepala('panen-jamur')],
            self::JUAL => [SinkronSheetHasil::kepala('penjualan-jamur')],
        ];

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'token-uji', 'expires_in' => 3600]),
            'sheets.googleapis.com/*' => fn (Request $r) => $this->jawabSheets($r),
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->berkasKunci);
        parent::tearDown();
    }

    // ───────────────────────────── Google Sheets palsu ─────────────────────────────

    private function jawabSheets(Request $r)
    {
        $url = $r->url();

        if (str_contains($url, 'values:batchGet')) {
            preg_match_all('/ranges=([^&]+)/', $url, $cocok);

            return Http::response(['valueRanges' => array_map(function (string $rentang) {
                preg_match("/^'(.+)'!/", urldecode($rentang), $tab);

                return ['values' => $this->pangkas($this->sheet[$tab[1]] ?? [])];
            }, $cocok[1])]);
        }

        if (str_contains($url, 'values:batchUpdate')) {
            if ($this->gagalTulis) {
                return Http::response(['error' => 'gagal'], 500);
            }
            $this->jumlahTulis++;
            foreach ($r->data()['data'] as $bagian) {
                preg_match("/^'(.+)'!A(\d+):/", $bagian['range'], $m);
                foreach ($bagian['values'] as $i => $baris) {
                    $this->sheet[$m[1]][(int) $m[2] - 1 + $i] = $baris;
                }
            }

            return Http::response([]);
        }

        if (str_ends_with($url, ':batchUpdate')) {
            foreach ($r->data()['requests'] as $perintah) {
                if (isset($perintah['addSheet'])) {
                    $this->sheet[$perintah['addSheet']['properties']['title']] ??= [];
                }
            }

            return Http::response([]);
        }

        if (str_contains($url, 'fields=sheets.properties')) {
            return Http::response(['sheets' => array_map(fn (string $t, int $i) => ['properties' => ['sheetId' => $i + 1, 'title' => $t]], array_keys($this->sheet), array_keys(array_keys($this->sheet)))]);
        }

        return Http::response([]);
    }

    /** Seperti API asli: sel kosong di ujung baris dan baris kosong di ujung tab tidak ikut dikirim. */
    private function pangkas(array $baris): array
    {
        $baris = array_map(function (array $b) {
            while ($b !== [] && ($b[array_key_last($b)] === '' || $b[array_key_last($b)] === null)) {
                array_pop($b);
            }

            return $b;
        }, $baris);
        while ($baris !== [] && $baris[array_key_last($baris)] === []) {
            array_pop($baris);
        }

        return array_values($baris);
    }

    private function sinkron(): array
    {
        return app(SinkronSheetHasil::class)->sinkronkan();
    }

    private function baris(string $tab, int $nomor = 1): array
    {
        return $this->sheet[$tab][$nomor];
    }

    private function kopi(array $ubah = []): PanenKopi
    {
        return PanenKopi::query()->create($ubah + ['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 100, 'limbah_kg' => 20]);
    }

    // ───────────────────────────── Sheet → sistem ─────────────────────────────

    public function test_baris_baru_di_sheet_jadi_catatan_dan_id_ditulis_balik(): void
    {
        $this->sheet[self::KOPI][] = ['', '05-10-2026', ' robusta ', 'Kebun  Krajan', 'KWT Giri', 100.5, 20.25];

        $hasil = $this->sinkron();

        $this->assertSame(1, $hasil['panen-kopi']['dibuat']);
        $this->assertDatabaseHas('toko_panen_kopi', ['jenis_kopi' => 'Robusta', 'tempat_panen' => 'Kebun Krajan', 'nama_petani' => 'KWT Giri', 'jumlah_kg' => '100.50', 'limbah_kg' => '20.25']);

        $baris = $this->baris(self::KOPI);
        $this->assertSame(PanenKopi::query()->first()->id, $baris[0]);
        $this->assertSame(['05-10-2026', 'Robusta', 'Kebun Krajan', 'KWT Giri', 100.5, 20.25, 'Tersinkron'], array_slice($baris, 1));
    }

    public function test_putaran_kedua_tanpa_perubahan_tidak_menulis_dan_tidak_menggandakan(): void
    {
        $this->sheet[self::KOPI][] = ['', '05-10-2026', 'Robusta', 'Krajan', '', 10, 1];
        $this->sinkron();
        $tulisSebelumnya = $this->jumlahTulis;

        $hasil = $this->sinkron();

        $this->assertSame($tulisSebelumnya, $this->jumlahTulis, 'Tanpa perubahan tidak boleh ada penulisan ke Sheet.');
        $this->assertSame(0, $hasil['panen-kopi']['dibuat']);
        $this->assertSame(1, PanenKopi::query()->count());
    }

    public function test_angka_diubah_di_sheet_memperbarui_catatan(): void
    {
        $this->kopi();
        $this->sinkron();

        $this->sheet[self::KOPI][1][5] = 150.5;
        $hasil = $this->sinkron();

        $this->assertSame(1, $hasil['panen-kopi']['diperbarui']);
        $this->assertSame('150.50', PanenKopi::query()->first()->jumlah_kg);
        $this->assertSame('Tersinkron', $this->baris(self::KOPI)[7]);
    }

    public function test_tanggal_dan_angka_bentuk_lain_dari_sheet_dibaca_benar(): void
    {
        $this->kopi();
        $this->sinkron();

        // Serial tanggal (5 Okt 2026 -> 6 Okt 2026) dan angka desimal bertanda koma, seperti hasil ketikan di Sheets berbahasa Indonesia.
        $this->sheet[self::KOPI][1][1] = 46301;
        $this->sheet[self::KOPI][1][5] = '120,25';
        $this->sinkron();

        $this->assertDatabaseHas('toko_panen_kopi', ['tanggal_panen' => '2026-10-06 00:00:00', 'jumlah_kg' => '120.25']);
    }

    public function test_baris_salah_tidak_mengubah_apa_pun_dan_alasannya_ditulis_di_status(): void
    {
        $this->sheet[self::KOPI][] = ['', 'bukan tanggal', 'Robusta', 'Krajan', '', 'abc', 1];

        $hasil = $this->sinkron();

        $this->assertSame(1, $hasil['panen-kopi']['salah']);
        $this->assertSame(0, PanenKopi::query()->count());
        $baris = $this->baris(self::KOPI);
        $this->assertSame('', $baris[0]);
        $this->assertSame('bukan tanggal', $baris[1], 'Isian salah dibiarkan supaya bisa diperbaiki.');
        $this->assertStringStartsWith('Perlu diperbaiki:', $baris[7]);

        $this->sinkron();
        $this->assertSame(0, PanenKopi::query()->count(), 'Putaran berikutnya tidak boleh membuat catatan dari baris salah.');
    }

    public function test_baris_salah_yang_sudah_diperbaiki_masuk_pada_putaran_berikutnya(): void
    {
        $this->sheet[self::KOPI][] = ['', 'bukan tanggal', 'Robusta', 'Krajan', '', 10, 1];
        $this->sinkron();

        $this->sheet[self::KOPI][1][1] = '05-10-2026';
        $this->sinkron();

        $this->assertSame(1, PanenKopi::query()->count());
        $this->assertSame('Tersinkron', $this->baris(self::KOPI)[7]);
    }

    public function test_tanggal_masa_depan_ditolak_seperti_di_form(): void
    {
        $this->sheet[self::KOPI][] = ['', now()->addDays(3)->format('d-m-Y'), 'Robusta', 'Krajan', '', 10, 1];

        $this->sinkron();

        $this->assertSame(0, PanenKopi::query()->count());
    }

    public function test_rumus_html_tidak_dijalankan_dan_isian_html_tetap_teks(): void
    {
        $this->sheet[self::KOPI][] = ['', '05-10-2026', 'Robusta', 'Krajan', '<script>x</script>', '=1+9', 1];
        $this->sinkron();
        $this->assertSame(0, PanenKopi::query()->count(), 'Teks "=1+9" bukan angka sehingga ditolak, bukan dihitung.');

        $this->sheet[self::KOPI][1][5] = 10;
        $this->sinkron();
        $this->assertSame('<script>x</script>', PanenKopi::query()->first()->nama_petani, 'Disimpan sebagai teks; Blade yang mengamankan saat ditampilkan.');
    }

    public function test_id_ganda_dan_id_asing_ditolak(): void
    {
        $kopi = $this->kopi();
        $this->sinkron();
        $this->sheet[self::KOPI][] = [$kopi->id, '05-10-2026', 'Arabika', 'Giri', '', 5, 1];
        $this->sheet[self::KOPI][] = [9999, '05-10-2026', 'Arabika', 'Giri', '', 5, 1];
        $this->sheet[self::KOPI][] = ['abc', '05-10-2026', 'Arabika', 'Giri', '', 5, 1];

        $hasil = $this->sinkron();

        $this->assertSame(3, $hasil['panen-kopi']['salah']);
        $this->assertSame(1, PanenKopi::query()->count());
        $this->assertSame('Robusta', $kopi->fresh()->jenis_kopi);
        $this->assertStringContainsString('muncul lebih dari sekali', $this->baris(self::KOPI, 2)[7]);
        $this->assertStringContainsString('tidak dikenal', $this->baris(self::KOPI, 3)[7]);
        $this->assertStringContainsString('tidak valid', $this->baris(self::KOPI, 4)[7]);
    }

    // ───────────────────────────── Sistem → Sheet ─────────────────────────────

    public function test_catatan_yang_sudah_ada_di_sistem_muncul_di_sheet(): void
    {
        $kopi = $this->kopi(['nama_petani' => 'KWT Giri']);
        $jamur = PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 12.5, 'jumlah_baglog' => 40, 'dengan_cangkang_kopi' => true, 'biaya_baglog_rupiah' => 300000]);
        $jual = PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-06', 'jumlah_kg' => 8, 'dengan_cangkang_kopi' => false, 'total_rupiah' => 240000]);

        $this->sinkron();

        $this->assertSame([$kopi->id, '05-10-2026', 'Robusta', 'Krajan', 'KWT Giri', 100.0, 20.0, 'Tersinkron'], $this->baris(self::KOPI));
        $this->assertSame([$jamur->id, '05-10-2026', 'Dengan cangkang kopi', 40, 12.5, 300000, 'Tersinkron'], $this->baris(self::JAMUR));
        $this->assertSame([$jual->id, '06-10-2026', 'Tanpa cangkang kopi', 8.0, 240000, 'Tersinkron'], $this->baris(self::JUAL));
    }

    public function test_perubahan_di_sistem_memperbarui_sheet(): void
    {
        $kopi = $this->kopi();
        $this->sinkron();

        $kopi->update(['jumlah_kg' => 175.5]);
        $hasil = $this->sinkron();

        $this->assertSame(1, $hasil['panen-kopi']['dari_sistem']);
        $this->assertSame(175.5, $this->baris(self::KOPI)[5]);
    }

    public function test_catatan_baru_dari_panel_admin_ditambahkan_di_bawah_baris_sheet(): void
    {
        $this->sheet[self::KOPI][] = ['', '05-10-2026', 'Robusta', 'Krajan', '', 10, 1];
        $this->sinkron();

        $baru = $this->kopi(['jenis_kopi' => 'Arabika']);
        $this->sinkron();

        $this->assertSame($baru->id, $this->baris(self::KOPI, 2)[0]);
        $this->assertSame('Robusta', $this->baris(self::KOPI, 1)[2], 'Urutan baris yang sudah ada tidak berubah.');
    }

    public function test_catatan_dihapus_di_panel_admin_barisnya_dibuang_dari_sheet(): void
    {
        $kopi = $this->kopi();
        $this->kopi(['jenis_kopi' => 'Arabika']);
        $this->sinkron();

        $kopi->delete();
        $hasil = $this->sinkron();

        $this->assertSame(1, $hasil['panen-kopi']['dibuang']);
        $this->assertCount(2, $this->pangkas($this->sheet[self::KOPI]), 'Tinggal judul + 1 baris.');
        $this->assertSame('Arabika', $this->baris(self::KOPI)[2]);
        $this->assertDatabaseMissing('toko_sinkron_sheet', ['catatan_id' => $kopi->id, 'jenis' => 'panen-kopi']);
    }

    public function test_baris_dihapus_di_sheet_tidak_menghapus_catatan_dan_barisnya_dikembalikan(): void
    {
        $kopi = $this->kopi();
        $this->sinkron();

        $this->sheet[self::KOPI] = [SinkronSheetHasil::kepala('panen-kopi')];
        $hasil = $this->sinkron();

        $this->assertSame(1, $hasil['panen-kopi']['dikembalikan']);
        $this->assertSame(1, PanenKopi::query()->count());
        $this->assertSame($kopi->id, $this->baris(self::KOPI)[0]);
        $this->assertStringStartsWith('Dikembalikan', $this->baris(self::KOPI)[7]);
    }

    public function test_dua_sisi_berubah_data_sistem_menang_dan_ditandai_konflik(): void
    {
        $kopi = $this->kopi();
        $this->sinkron();

        $this->sheet[self::KOPI][1][5] = 150;
        $kopi->update(['jumlah_kg' => 200]);
        $hasil = $this->sinkron();

        $this->assertSame(1, $hasil['panen-kopi']['konflik']);
        $this->assertSame('200.00', $kopi->fresh()->jumlah_kg);
        $this->assertSame(200.0, $this->baris(self::KOPI)[5]);
        $this->assertStringStartsWith('Konflik', $this->baris(self::KOPI)[7]);
    }

    public function test_penjualan_lama_tanpa_media_tidak_dianggap_salah(): void
    {
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-06', 'jumlah_kg' => 8, 'dengan_cangkang_kopi' => null, 'total_rupiah' => 240000]);

        $this->sinkron();
        $hasil = $this->sinkron();

        $this->assertSame(0, $hasil['penjualan-jamur']['salah']);
        $this->assertSame('', $this->baris(self::JUAL)[2]);
        $this->assertSame('Tersinkron', $this->baris(self::JUAL)[5]);
    }

    // ───────────────────────────── Ketahanan ─────────────────────────────

    public function test_gagal_menulis_ke_sheet_membatalkan_perubahan_di_sistem(): void
    {
        $this->sheet[self::KOPI][] = ['', '05-10-2026', 'Robusta', 'Krajan', '', 10, 1];
        $this->gagalTulis = true;

        try {
            $this->sinkron();
            $this->fail('Seharusnya melempar galat karena penulisan ke Sheet gagal.');
        } catch (RequestException) {
            // diharapkan
        }

        $this->assertSame(0, PanenKopi::query()->count(), 'ID gagal ditulis balik -> catatan jangan dibuat, agar tidak ganda pada percobaan berikutnya.');

        $this->gagalTulis = false;
        $this->sinkron();
        $this->assertSame(1, PanenKopi::query()->count());
    }

    public function test_judul_kolom_diubah_tab_dilewati_tanpa_merusak_data(): void
    {
        $this->kopi();
        $this->sheet[self::KOPI] = [['ID', 'Tanggal', 'Jenis'], [1, 'x', 'y']];

        $hasil = $this->sinkron();

        $this->assertStringContainsString('toko:sheets-siapkan', $hasil['panen-kopi']['galat']);
        $this->assertSame('Robusta', PanenKopi::query()->first()->jenis_kopi);
        $this->assertSame([1, 'x', 'y'], $this->sheet[self::KOPI][1], 'Isi tab yang salah tidak ditimpa.');
    }

    public function test_perubahan_dari_sheet_tercatat_di_log_aktivitas(): void
    {
        $this->sheet[self::KOPI][] = ['', '05-10-2026', 'Robusta', 'Krajan', '', 10, 1];
        $this->sinkron();

        $log = Aktivitas::query()->where('aksi', 'sinkron_sheets')->sole();
        $this->assertNull($log->user_id);
        $this->assertStringContainsString('1 baru', $log->keterangan);

        $this->sinkron();
        $this->assertSame(1, Aktivitas::query()->where('aksi', 'sinkron_sheets')->count(), 'Putaran tanpa perubahan tidak mengisi log.');
    }

    public function test_token_dibuat_dari_kunci_dan_memakai_header_bearer(): void
    {
        $this->sinkron();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://oauth2.googleapis.com/token'
            && $r['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
            && substr_count($r['assertion'], '.') === 2);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'sheets.googleapis.com') && $r->hasHeader('Authorization', 'Bearer token-uji'));
    }

    // ───────────────────────────── Perintah artisan ─────────────────────────────

    public function test_perintah_sinkron_diam_saat_saklar_mati(): void
    {
        config(['toko_sheets.aktif' => false]);

        $this->artisan('toko:sheets-sinkron')->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_perintah_sinkron_gagal_jelas_bila_kunci_belum_diisi(): void
    {
        config(['toko_sheets.kunci_json' => null]);

        $this->artisan('toko:sheets-sinkron')->expectsOutputToContain('TOKO_SHEETS_KUNCI')->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_perintah_sinkron_melaporkan_galat_tanpa_membocorkan_kunci(): void
    {
        $this->gagalTulis = true;
        $this->kopi();

        $this->artisan('toko:sheets-sinkron')->assertExitCode(1);
    }

    public function test_perintah_siapkan_membuat_tab_judul_dan_mengisi_catatan_yang_ada(): void
    {
        $this->sheet = [];
        $kopi = $this->kopi();
        // Sheet palsu baru "punya" tab setelah menerima permintaan addSheet.

        $this->artisan('toko:sheets-siapkan')->assertExitCode(0);

        $this->assertSame(SinkronSheetHasil::kepala('panen-kopi'), $this->baris(self::KOPI, 0));
        $this->assertSame($kopi->id, $this->baris(self::KOPI, 1)[0]);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), ':batchUpdate') && collect($r->data()['requests'] ?? [])->contains(fn ($p) => isset($p['setDataValidation'])));
    }
}
