<?php

namespace Tests\Feature\Ews\Admin;

use App\Models\Ews\Aktivitas;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Pengaturan;
use App\Models\Ews\Wilayah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PengaturanAdminTest extends TestCase
{
    use RefreshDatabase;

    private const AMBANG = [
        'kelembapan_tanah_waspada' => 55, 'kelembapan_tanah_validasi' => 85,
        'intensitas_hujan_waspada' => 12.5, 'intensitas_hujan_validasi' => 25,
        'kemiringan_waspada' => 3, 'kemiringan_validasi' => 9,
    ];

    private const BENTUK = '[[-7.1,109.1],[-7.1,109.2],[-7.2,109.2]]';

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_ambang_disimpan_dipakai_dan_dicatat(): void
    {
        $this->get(route('admin.ews.ambang.edit'))->assertOk()->assertSee('sementara');

        $this->put(route('admin.ews.ambang.update'), self::AMBANG)->assertSessionHas('sukses');

        $this->assertSame(['waspada' => 3.0, 'validasi' => 9.0], Pengaturan::ambang()['kemiringan']);
        $this->assertSame(['waspada' => 12.5, 'validasi' => 25.0], Pengaturan::ambang()['intensitas_hujan']);
        $this->assertStringContainsString('Kemiringan: 5/15 menjadi 3/9', Aktivitas::query()->sole()->keterangan);
    }

    public function test_simpan_tanpa_perubahan_tidak_mengotori_log(): void
    {
        $this->put(route('admin.ews.ambang.update'), self::AMBANG);
        $this->put(route('admin.ews.ambang.update'), self::AMBANG);

        $this->assertSame(1, Aktivitas::query()->count());
    }

    #[DataProvider('ambangSalah')]
    public function test_ambang_tidak_valid_ditolak(array $ubah, string $kolom): void
    {
        $this->put(route('admin.ews.ambang.update'), [...self::AMBANG, ...$ubah])->assertSessionHasErrors($kolom);

        $this->assertSame(0, Pengaturan::query()->count());
    }

    public static function ambangSalah(): array
    {
        return [
            'validasi sama dengan waspada' => [['kelembapan_tanah_validasi' => 55], 'kelembapan_tanah_validasi'],
            'validasi di bawah waspada' => [['intensitas_hujan_validasi' => 5], 'intensitas_hujan_validasi'],
            'kemiringan terbalik' => [['kemiringan_waspada' => 20], 'kemiringan_validasi'],
            'tanah lewat 100' => [['kelembapan_tanah_validasi' => 101], 'kelembapan_tanah_validasi'],
            'kemiringan lewat 90' => [['kemiringan_validasi' => 91], 'kemiringan_validasi'],
            'waspada nol' => [['intensitas_hujan_waspada' => 0], 'intensitas_hujan_waspada'],
            'kosong' => [['kemiringan_waspada' => ''], 'kemiringan_waspada'],
        ];
    }

    public function test_wilayah_ditambah_dan_id_alat_tampil(): void
    {
        $this->get(route('admin.ews.wilayah.create'))->assertOk();

        $this->post(route('admin.ews.wilayah.store'), ['nama' => 'Lereng Baru', 'poligon' => '[[-7.1,109.1],[-7.1,109.2],[-7.2,109.2],[-7.1,109.1]]'])
            ->assertRedirect(route('admin.ews.wilayah.index'));

        $w = Wilayah::query()->sole();
        $this->assertCount(3, $w->poligon, 'titik penutup dibuang');
        $this->get(route('admin.ews.wilayah.index'))->assertSee('Lereng Baru')->assertSee('ID alat')->assertSeeInOrder(['ID alat', (string) $w->id]);
        $this->assertSame('tambah_wilayah', Aktivitas::query()->sole()->aksi);
    }

    public function test_lokasi_alat_disimpan_dan_poligon_bisa_satu_titik_per_baris(): void
    {
        $this->post(route('admin.ews.wilayah.store'), [
            'nama' => 'Area Alat',
            'poligon' => "-7.186448, 109.591973\n-7.186262, 109.591157\n-7.187830, 109.591357\n-7.187723, 109.591529",
            'lintang' => '-7.186625',
            'bujur' => '109.591824',
        ])->assertSessionHasNoErrors();

        $w = Wilayah::query()->sole();
        $this->assertCount(4, $w->poligon);
        $this->assertSame([-7.186625, 109.591824], [$w->lintang, $w->bujur]);
        $this->get(route('admin.ews.wilayah.index'))->assertSee('Lokasi alat: -7.186625, 109.591824');
        $this->getJson('/api/ews/data')->assertJsonPath('wilayah.0.lintang', -7.186625);
    }

    public function test_lokasi_alat_boleh_kosong_tetapi_harus_berpasangan(): void
    {
        $this->post(route('admin.ews.wilayah.store'), ['nama' => 'Tanpa Lokasi', 'poligon' => self::BENTUK])->assertSessionHasNoErrors();
        $this->assertNull(Wilayah::query()->sole()->lintang);

        $this->post(route('admin.ews.wilayah.store'), ['nama' => 'Setengah', 'poligon' => self::BENTUK, 'lintang' => '-7.1'])->assertSessionHasErrors('bujur');
        $this->post(route('admin.ews.wilayah.store'), ['nama' => 'Mustahil', 'poligon' => self::BENTUK, 'lintang' => '-97', 'bujur' => '109'])->assertSessionHasErrors('lintang');
    }

    #[DataProvider('poligonSalah')]
    public function test_poligon_tidak_valid_ditolak(string $poligon): void
    {
        $this->post(route('admin.ews.wilayah.store'), ['nama' => 'Salah', 'poligon' => $poligon])->assertSessionHasErrors('poligon');

        $this->assertDatabaseCount('ews_wilayah', 0);
    }

    public static function poligonSalah(): array
    {
        return [
            'kosong' => [''],
            'bukan json' => ['bukan json'],
            'dua titik' => ['[[-7.1,109.1],[-7.2,109.2]]'],
            'garis' => ['[[-7.1,109.1],[-7.2,109.2],[-7.3,109.3]]'],
            'menyilang' => ['[[-7.1,109.1],[-7.2,109.2],[-7.1,109.2],[-7.2,109.1]]'],
            'koordinat mustahil' => ['[[-97.1,109.1],[-7.2,109.2],[-7.1,109.2]]'],
        ];
    }

    public function test_nama_harus_unik_termasuk_yang_terhapus(): void
    {
        Wilayah::factory()->create(['nama' => 'Dipakai']);
        Wilayah::factory()->create(['nama' => 'Terhapus'])->delete();

        $this->post(route('admin.ews.wilayah.store'), ['nama' => 'Dipakai', 'poligon' => self::BENTUK])->assertSessionHasErrors('nama');
        $this->post(route('admin.ews.wilayah.store'), ['nama' => 'Terhapus', 'poligon' => self::BENTUK])->assertSessionHasErrors('nama');
    }

    public function test_wilayah_diubah_tanpa_bentrok_dengan_namanya_sendiri(): void
    {
        $w = Wilayah::factory()->create(['nama' => 'Lama']);

        $this->put(route('admin.ews.wilayah.update', $w), ['nama' => 'Lama', 'poligon' => self::BENTUK])->assertSessionHasNoErrors();
        $this->put(route('admin.ews.wilayah.update', $w), ['nama' => 'Baru', 'poligon' => self::BENTUK])->assertSessionHasNoErrors();

        $this->assertSame('Baru', $w->refresh()->nama);
    }

    public function test_hapus_lunak_menjaga_riwayat_dan_laporan_menunggu_ditutup(): void
    {
        $w = Wilayah::factory()->create();
        $laporan = Pembacaan::factory()->menunggu()->create(['wilayah_id' => $w->id]);

        $this->delete(route('admin.ews.wilayah.destroy', $w))->assertSessionHas('sukses');

        $this->assertSoftDeleted($w);
        $this->assertDatabaseCount('ews_pembacaan', 1);
        $this->assertSame(Pembacaan::VALIDASI_DITOLAK, $laporan->refresh()->validasi);
        $this->get(route('admin.ews.validasi.index'))->assertOk()->assertSee('Tidak ada laporan');
    }

    public function test_wilayah_awas_tidak_bisa_dihapus(): void
    {
        $w = Wilayah::factory()->awas()->create();

        $this->delete(route('admin.ews.wilayah.destroy', $w))->assertSessionHas('galat');

        $this->assertNotSoftDeleted($w);
    }

    public function test_wilayah_terhapus_bisa_dipulihkan(): void
    {
        $w = Wilayah::factory()->create(['nama' => 'Kembali']);
        $w->delete();
        $this->get(route('admin.ews.wilayah.index'))->assertSee('Wilayah yang dihapus')->assertSee('Kembali');

        $this->post(route('admin.ews.wilayah.pulihkan', $w->id))->assertSessionHas('sukses');

        $this->assertNotSoftDeleted($w);
    }

    public function test_riwayat_filter_wilayah_dan_tanggal(): void
    {
        $a = Wilayah::factory()->create(['nama' => 'Wilayah Satu']);
        $b = Wilayah::factory()->create(['nama' => 'Wilayah Dua']);
        Pembacaan::factory()->create(['wilayah_id' => $a->id]);
        Pembacaan::factory()->create(['wilayah_id' => $b->id]);
        (new Pembacaan)->forceFill(['wilayah_id' => $a->id, 'status' => 'Aman', 'warna' => 'hijau', 'pemicu' => 'data-lama', 'created_at' => now()->subDays(10)])->save();

        $this->get(route('admin.ews.riwayat.index'))->assertOk()->assertSee('Wilayah Dua');
        $this->get(route('admin.ews.riwayat.index', ['wilayah' => $a->id]))
            ->assertSeeInOrder(['Wilayah Satu', 'Wilayah Satu'])->assertViewHas('pembacaan', fn ($p) => $p->total() === 2);
        $this->get(route('admin.ews.riwayat.index', ['sampai' => now()->subDays(5)->toDateString()]))
            ->assertSee('data-lama')->assertViewHas('pembacaan', fn ($p) => $p->total() === 1);
        $this->get(route('admin.ews.riwayat.index', ['dari' => now()->toDateString(), 'sampai' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('sampai');
    }

    public function test_riwayat_kosong_ramah(): void
    {
        $this->get(route('admin.ews.riwayat.index'))->assertOk()->assertSee('Tidak ada data pada pilihan ini');
    }

    public function test_ekspor_csv_mengikuti_filter_titik_desimal_dan_aman_dari_rumus(): void
    {
        $a = Wilayah::factory()->create(['nama' => '=HYPERLINK("x")']);
        $b = Wilayah::factory()->create(['nama' => 'Lain']);
        Pembacaan::factory()->create(['wilayah_id' => $a->id, 'kelembapan_tanah' => 63.4, 'intensitas_hujan' => null]);
        Pembacaan::factory()->create(['wilayah_id' => $b->id]);

        $respons = $this->get(route('admin.ews.riwayat.ekspor', ['wilayah' => $a->id]))->assertOk();
        $this->assertStringContainsString('text/csv', $respons->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $respons->headers->get('Content-Disposition'));

        $baris = array_map(fn ($b) => str_getcsv($b, escape: ''), array_filter(explode("\n", $respons->streamedContent())));
        $this->assertCount(2, $baris);
        $this->assertSame('wilayah', $baris[0][2]);
        $this->assertSame('\'=HYPERLINK("x")', $baris[1][2]);
        $this->assertSame('63.4', $baris[1][3]);
        $this->assertSame('', $baris[1][4], 'sensor kosong tetap kosong, bukan 0');
    }

    public function test_log_aktivitas_tampil(): void
    {
        $this->get(route('admin.ews.aktivitas'))->assertOk()->assertSee('Belum ada aktivitas');

        Aktivitas::catat(auth()->user(), 'tes', 'Keterangan <b>uji</b>');

        $this->get(route('admin.ews.aktivitas'))->assertSee('Keterangan &lt;b&gt;uji&lt;/b&gt;', false);
    }

    public function test_status_alat_online_offline_dan_tanpa_rahasia(): void
    {
        config(['ews.alat_offline_menit' => 15, 'ews.api_key' => 'KUNCI-RAHASIA']);
        $hidup = Wilayah::factory()->create(['nama' => 'Hidup']);
        $mati = Wilayah::factory()->create(['nama' => 'Mati']);
        Wilayah::factory()->create(['nama' => 'Baru']);
        (new Pembacaan)->forceFill(['wilayah_id' => $mati->id, 'status' => 'Aman', 'warna' => 'hijau', 'created_at' => now()->subHour()])->save();
        Pembacaan::factory()->create(['wilayah_id' => $hidup->id]);

        $this->get(route('admin.ews.alat'))->assertOk()
            ->assertSeeInOrder(['Hidup', 'Mengirim', 'Mati', 'Tidak mengirim', 'Baru', 'Belum terhubung'])
            ->assertDontSee('KUNCI-RAHASIA')
            ->assertDontSee('masih kosong');
    }

    public function test_status_alat_memperingatkan_konfigurasi_kosong(): void
    {
        config(['ews.api_key' => '']);

        $this->get(route('admin.ews.alat'))->assertSee('EWS_API_KEY di .env masih kosong');
    }

    public function test_simulator_menolak_kiriman_kosong_dan_berfungsi(): void
    {
        $w = Wilayah::factory()->create();
        $this->get(route('admin.ews.simulator.index'))->assertOk();

        $this->post(route('admin.ews.simulator.kirim'), ['wilayah_id' => $w->id])->assertSessionHas('galat');
        $this->post(route('admin.ews.simulator.kirim'), ['wilayah_id' => $w->id, 'kelembapan_tanah' => 200])->assertSessionHasErrors('kelembapan_tanah');
        $this->assertDatabaseCount('ews_pembacaan', 0);

        $this->post(route('admin.ews.simulator.kirim'), ['wilayah_id' => $w->id, 'kabel_putus' => '1'])->assertSessionHas('sukses');
        $this->assertTrue($w->refresh()->status_awas);
    }

    public function test_simulator_mati_di_produksi(): void
    {
        $this->app['env'] = 'production';

        $this->get(route('admin.ews.simulator.index'))->assertNotFound();
    }

    public function test_menu_admin_berisi_modul_ews_dan_jumlah_menunggu(): void
    {
        Pembacaan::factory()->menunggu()->create();

        $this->get('/admin')->assertOk()->assertSee('Validasi dan Awas')->assertSee('Daftar wilayah')->assertSee('Simulator')->assertSee('menunggu');
    }
}
