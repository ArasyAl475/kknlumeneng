<?php

namespace Tests\Feature\Ews\Admin;

use App\Models\Ews\Aktivitas;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidasiAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
        config(['ews.api_key' => 'k', 'ews.validasi_jeda_menit' => 30]);
    }

    private function kirim(Wilayah $w, array $data)
    {
        return $this->postJson('/api/sensor', ['wilayah_id' => $w->id, ...$data], ['X-API-KEY' => 'k']);
    }

    public function test_antrean_menampilkan_semua_nilai_sensor(): void
    {
        $w = Wilayah::factory()->create(['nama' => 'Lereng Uji']);
        Pembacaan::factory()->menunggu()->create(['wilayah_id' => $w->id, 'intensitas_hujan' => null, 'kelembapan_udara' => 88.5]);

        $this->get(route('admin.ews.validasi.index'))->assertOk()
            ->assertSee('Lereng Uji')
            ->assertSee('90%')
            ->assertSee('lewat batas')
            ->assertSee('Tidak ada data')
            ->assertSee('88,5%');
    }

    public function test_antrean_tanpa_n_plus_1(): void
    {
        foreach (range(1, 3) as $i) {
            Pembacaan::factory()->menunggu()->create();
        }

        // preventLazyLoading aktif saat tes: akses relasi yang tidak dimuat akan melempar galat.
        $this->get(route('admin.ews.validasi.index'))->assertOk();
    }

    public function test_setujui_menjadikan_awas_dan_mencatat_log(): void
    {
        $w = Wilayah::factory()->create(['nama' => 'Lereng Uji']);
        $laporan = Pembacaan::factory()->menunggu()->create(['wilayah_id' => $w->id]);

        $this->post(route('admin.ews.validasi.setujui', $laporan))->assertRedirect()->assertSessionHas('sukses');

        $laporan->refresh();
        $this->assertSame(Pembacaan::VALIDASI_DISETUJUI, $laporan->validasi);
        $this->assertSame('Awas', $laporan->status);
        $this->assertSame('merah', $laporan->warna);
        $this->assertSame($this->admin->id, $laporan->divalidasi_oleh);
        $this->assertNotNull($laporan->divalidasi_pada);

        $w->refresh();
        $this->assertTrue($w->status_awas);
        $this->assertSame('Dinyatakan admin setelah validasi', $w->awas_sumber);

        $this->assertSame('setujui_validasi', Aktivitas::query()->sole()->aksi);
        $this->assertSame($this->admin->name, Aktivitas::query()->sole()->nama_admin);
    }

    public function test_tolak_tidak_mengubah_status_wilayah(): void
    {
        $w = Wilayah::factory()->create();
        $laporan = Pembacaan::factory()->menunggu()->create(['wilayah_id' => $w->id]);

        $this->post(route('admin.ews.validasi.tolak', $laporan))->assertSessionHas('sukses');

        $this->assertSame(Pembacaan::VALIDASI_DITOLAK, $laporan->refresh()->validasi);
        $this->assertSame('Waspada', $laporan->status);
        $this->assertFalse($w->refresh()->status_awas);
    }

    public function test_laporan_tidak_bisa_diputuskan_dua_kali(): void
    {
        $laporan = Pembacaan::factory()->menunggu()->create();

        $this->post(route('admin.ews.validasi.tolak', $laporan));
        $this->post(route('admin.ews.validasi.setujui', $laporan))->assertSessionHas('galat');

        $this->assertSame(Pembacaan::VALIDASI_DITOLAK, $laporan->refresh()->validasi);
        $this->assertFalse($laporan->wilayah()->first()->status_awas);
        $this->assertSame(1, Aktivitas::query()->count());
    }

    public function test_setujui_saat_sudah_awas_tidak_mengubah_sumber_awas(): void
    {
        $w = Wilayah::factory()->awas('Kabel EWS putus (otomatis)')->create();
        $laporan = Pembacaan::factory()->menunggu()->create(['wilayah_id' => $w->id]);

        $this->post(route('admin.ews.validasi.setujui', $laporan))->assertSessionHas('sukses');

        $this->assertSame('Kabel EWS putus (otomatis)', $w->refresh()->awas_sumber);
    }

    public function test_cabut_butuh_alasan_dan_konfirmasi(): void
    {
        $w = Wilayah::factory()->awas()->create();
        $url = route('admin.ews.awas.cabut', $w);

        $this->post($url, [])->assertSessionHasErrors(['alasan', 'konfirmasi']);
        $this->post($url, ['alasan' => 'aman', 'konfirmasi' => '1'])->assertSessionHasErrors('alasan');
        $this->post($url, ['alasan' => 'Sudah diperiksa kader'])->assertSessionHasErrors('konfirmasi');
        $this->assertTrue($w->refresh()->status_awas);
    }

    public function test_cabut_mengembalikan_status_ke_data_sensor_dan_mencatat_alasan(): void
    {
        $w = Wilayah::factory()->awas()->create(['nama' => 'Lereng Uji']);
        Pembacaan::factory()->create(['wilayah_id' => $w->id]);
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Awas');

        $this->post(route('admin.ews.awas.cabut', $w), ['alasan' => 'Sudah diperiksa kader', 'konfirmasi' => '1'])
            ->assertRedirect(route('admin.ews.validasi.index'));

        $w->refresh();
        $this->assertFalse($w->status_awas);
        $this->assertNull($w->awas_sumber);
        $this->assertNull($w->awas_sejak);
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Aman');
        $this->assertStringContainsString('Sudah diperiksa kader', Aktivitas::query()->sole()->keterangan);
    }

    public function test_cabut_tanpa_kiriman_baru_tidak_tersangkut_di_awas(): void
    {
        $w = Wilayah::factory()->create();
        $laporan = Pembacaan::factory()->menunggu()->create(['wilayah_id' => $w->id]);
        $this->post(route('admin.ews.validasi.setujui', $laporan));
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Awas');

        $this->post(route('admin.ews.awas.cabut', $w), ['alasan' => 'Sudah diperiksa kader', 'konfirmasi' => '1']);

        // Pembacaan terakhir tetap berangka 90%: Waspada menurut sensor, dan riwayatnya tetap tercatat Awas.
        $this->getJson('/api/ews/data')
            ->assertJsonPath('utama.status', 'Waspada')
            ->assertJsonPath('utama.warna', 'kuning')
            ->assertJsonPath('utama.pemicu', 'Kelembapan tanah 90%');
        $this->assertSame('Awas', $laporan->refresh()->status);
        $this->get(route('admin.ews.wilayah.index'))->assertSee('Waspada');
    }

    public function test_cabut_kabel_putus_tanpa_kiriman_baru_kembali_aman(): void
    {
        $w = Wilayah::factory()->create();
        $this->kirim($w, ['kabel_putus' => true]);

        $this->post(route('admin.ews.awas.cabut', $w), ['alasan' => 'Kabel sudah diperbaiki', 'konfirmasi' => '1']);

        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Aman')->assertJsonPath('utama.pemicu', 'Semua sensor normal');
    }

    public function test_cabut_wilayah_yang_tidak_awas_ditolak(): void
    {
        $w = Wilayah::factory()->create();

        $this->post(route('admin.ews.awas.cabut', $w), ['alasan' => 'Sudah diperiksa kader', 'konfirmasi' => '1'])->assertSessionHas('galat');
        $this->get(route('admin.ews.awas.konfirmasi', $w))->assertRedirect()->assertSessionHas('galat');
        $this->assertSame(0, Aktivitas::query()->count());
    }

    public function test_kabel_masih_putus_setelah_dicabut_kembali_awas(): void
    {
        $w = Wilayah::factory()->create();
        $this->kirim($w, ['kabel_putus' => true]);
        $this->post(route('admin.ews.awas.cabut', $w), ['alasan' => 'Salah cabut, tes', 'konfirmasi' => '1']);

        $this->kirim($w, ['kabel_putus' => true])->assertJsonPath('data.status', 'Awas');

        $this->assertTrue($w->refresh()->status_awas);
    }

    public function test_nyatakan_awas_butuh_alasan_dan_konfirmasi(): void
    {
        $w = Wilayah::factory()->create();
        $url = route('admin.ews.awas.nyatakan', $w);

        $this->post($url, [])->assertSessionHasErrors(['alasan', 'konfirmasi']);
        $this->post($url, ['alasan' => 'aman', 'konfirmasi' => '1'])->assertSessionHasErrors('alasan');
        $this->post($url, ['alasan' => 'Kader melihat retakan'])->assertSessionHasErrors('konfirmasi');
        $this->assertFalse($w->refresh()->status_awas);
    }

    public function test_nyatakan_awas_mengunci_status_dan_mencatat_alasan_tanpa_membocorkannya(): void
    {
        $w = Wilayah::factory()->create(['nama' => 'Lereng Uji']);
        Pembacaan::factory()->create(['wilayah_id' => $w->id, 'kelembapan_tanah' => 20, 'status' => 'Aman', 'warna' => 'hijau']);

        $this->post(route('admin.ews.awas.nyatakan', $w), ['alasan' => 'Kader melihat retakan baru', 'konfirmasi' => '1'])
            ->assertRedirect(route('admin.ews.validasi.index'))->assertSessionHas('sukses');

        $w->refresh();
        $this->assertTrue($w->status_awas);
        $this->assertSame('Dinyatakan admin (manual)', $w->awas_sumber);
        $this->assertNotNull($w->awas_sejak);

        // Warga melihat Awas, tetapi alasan bebas dari admin hanya ada di log admin.
        $this->getJson('/api/ews/data')
            ->assertJsonPath('utama.status', 'Awas')
            ->assertJsonPath('utama.pemicu', 'Dinyatakan admin (manual)');
        $log = Aktivitas::query()->sole();
        $this->assertSame('nyatakan_awas', $log->aksi);
        $this->assertStringContainsString('Kader melihat retakan baru', $log->keterangan);
        $this->assertStringNotContainsString('retakan', json_encode($this->getJson('/api/ews/data')->json()));

        // Data sensor yang normal tidak menurunkan Awas; hanya admin yang bisa mencabutnya.
        $this->kirim($w, ['kelembapan_tanah' => 10]);
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Awas');
    }

    public function test_nyatakan_awas_pada_wilayah_yang_sudah_awas_ditolak(): void
    {
        $w = Wilayah::factory()->awas('Kabel EWS putus (otomatis)')->create();

        $this->post(route('admin.ews.awas.nyatakan', $w), ['alasan' => 'Kader melihat retakan', 'konfirmasi' => '1'])->assertSessionHas('galat');
        $this->get(route('admin.ews.awas.form-nyatakan', $w))->assertRedirect()->assertSessionHas('galat');

        $this->assertSame('Kabel EWS putus (otomatis)', $w->refresh()->awas_sumber);
        $this->assertSame(0, Aktivitas::query()->count());
    }

    public function test_halaman_validasi_menandai_alat_diam_saat_waspada_paling_atas(): void
    {
        config(['ews.alat_offline_menit' => 15]);
        $sunyi = Wilayah::factory()->create(['nama' => 'Lereng Sunyi']);
        $biasa = Wilayah::factory()->create(['nama' => 'Jalan Biasa']);
        Pembacaan::factory()->create(['wilayah_id' => $biasa->id]);
        Pembacaan::factory()->create(['wilayah_id' => $sunyi->id, 'kelembapan_tanah' => 60, 'status' => 'Waspada', 'warna' => 'kuning', 'pemicu' => 'Kelembapan tanah 60%']);
        $this->travel(16)->minutes();

        $html = $this->get(route('admin.ews.validasi.index'))->assertOk()
            ->assertSee('Alat berhenti mengirim data saat kondisi Waspada: Lereng Sunyi')
            ->assertSee('Nyatakan Awas')
            ->getContent();

        $this->assertLessThan(strrpos($html, 'Jalan Biasa'), strrpos($html, 'Lereng Sunyi'), 'wilayah yang alatnya diam saat Waspada tampil lebih dulu');
    }

    public function test_alur_lengkap_dari_simulator_sampai_dicabut(): void
    {
        $w = Wilayah::factory()->create(['nama' => 'Lereng Uji']);
        $simulator = route('admin.ews.simulator.kirim');

        // 1. Data masuk melewati batas validasi: Waspada + antrean validasi.
        $this->post($simulator, ['wilayah_id' => $w->id, 'kelembapan_tanah' => 90, 'intensitas_hujan' => 25])
            ->assertSessionHas('sukses', fn ($p) => str_contains($p, 'Laporan masuk antrean validasi'));
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Waspada');

        // 2. Kiriman berikutnya tidak membanjiri antrean.
        $this->post($simulator, ['wilayah_id' => $w->id, 'kelembapan_tanah' => 92]);
        $laporan = Pembacaan::query()->where('validasi', Pembacaan::VALIDASI_MENUNGGU)->sole();

        // 3. Admin menyetujui: Awas.
        $this->post(route('admin.ews.validasi.setujui', $laporan));
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Awas');

        // 4. Data membaik, Awas tetap ditahan.
        $this->post($simulator, ['wilayah_id' => $w->id, 'kelembapan_tanah' => 20]);
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Awas');

        // 5. Admin mencabut: kembali mengikuti data.
        $this->post(route('admin.ews.awas.cabut', $w), ['alasan' => 'Lereng sudah diperiksa', 'konfirmasi' => '1']);
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Aman');

        $this->assertSame(['setujui_validasi', 'cabut_awas'], Aktivitas::query()->orderBy('id')->pluck('aksi')->all());
    }
}
