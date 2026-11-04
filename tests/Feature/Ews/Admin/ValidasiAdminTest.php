<?php

namespace Tests\Feature\Ews\Admin;

use App\Jobs\Ews\KirimPesanTelegram;
use App\Models\Ews\Aktivitas;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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
        config(['ews.api_key' => 'k', 'ews.telegram.token' => 't', 'ews.telegram.chat_id' => 'c', 'ews.validasi_jeda_menit' => 30]);
        Bus::fake();
    }

    private function kirim(Wilayah $w, array $data)
    {
        return $this->postJson('/api/sensor', ['wilayah_id' => $w->id, ...$data], ['X-API-KEY' => 'k']);
    }

    public function test_antrean_menampilkan_semua_nilai_sensor(): void
    {
        $w = Wilayah::factory()->create(['nama' => 'Lereng Uji']);
        Pembacaan::factory()->menunggu()->create(['wilayah_id' => $w->id, 'curah_hujan' => null, 'kelembapan_udara' => 88.5]);

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

    public function test_setujui_menjadikan_awas_mengirim_telegram_dan_mencatat_log(): void
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

        Bus::assertDispatchedAfterResponseTimes(KirimPesanTelegram::class, 1);
        Bus::assertDispatchedAfterResponse(KirimPesanTelegram::class, fn ($j) => str_contains($j->pesan, 'dinyatakan admin') && str_contains($j->pesan, 'Lereng Uji'));
        $this->assertSame('setujui_validasi', Aktivitas::query()->sole()->aksi);
        $this->assertSame($this->admin->name, Aktivitas::query()->sole()->nama_admin);
    }

    public function test_tolak_tidak_mengubah_status_wilayah_dan_tanpa_telegram(): void
    {
        $w = Wilayah::factory()->create();
        $laporan = Pembacaan::factory()->menunggu()->create(['wilayah_id' => $w->id]);

        $this->post(route('admin.ews.validasi.tolak', $laporan))->assertSessionHas('sukses');

        $this->assertSame(Pembacaan::VALIDASI_DITOLAK, $laporan->refresh()->validasi);
        $this->assertSame('Waspada', $laporan->status);
        $this->assertFalse($w->refresh()->status_awas);
        Bus::assertNotDispatchedAfterResponse(KirimPesanTelegram::class);
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

    public function test_setujui_saat_sudah_awas_tidak_mengirim_telegram_lagi(): void
    {
        $w = Wilayah::factory()->awas('Kabel EWS putus (otomatis)')->create();
        $laporan = Pembacaan::factory()->menunggu()->create(['wilayah_id' => $w->id]);

        $this->post(route('admin.ews.validasi.setujui', $laporan))->assertSessionHas('sukses');

        $this->assertSame('Kabel EWS putus (otomatis)', $w->refresh()->awas_sumber);
        Bus::assertNotDispatchedAfterResponse(KirimPesanTelegram::class);
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
        Bus::assertDispatchedAfterResponseTimes(KirimPesanTelegram::class, 2);
    }

    public function test_alur_lengkap_dari_simulator_sampai_dicabut(): void
    {
        $w = Wilayah::factory()->create(['nama' => 'Lereng Uji']);
        $simulator = route('admin.ews.simulator.kirim');

        // 1. Data masuk melewati batas validasi: Waspada + antrean + Telegram "perlu validasi".
        $this->post($simulator, ['wilayah_id' => $w->id, 'kelembapan_tanah' => 90, 'curah_hujan' => 25])
            ->assertSessionHas('sukses', fn ($p) => str_contains($p, 'Laporan masuk antrean validasi'));
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Waspada');
        Bus::assertDispatchedAfterResponse(KirimPesanTelegram::class, fn ($j) => str_contains($j->pesan, 'PERLU VALIDASI'));

        // 2. Kiriman berikutnya tidak membanjiri antrean.
        $this->post($simulator, ['wilayah_id' => $w->id, 'kelembapan_tanah' => 92]);
        $laporan = Pembacaan::query()->where('validasi', Pembacaan::VALIDASI_MENUNGGU)->sole();

        // 3. Admin menyetujui: Awas + Telegram.
        $this->post(route('admin.ews.validasi.setujui', $laporan));
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Awas');
        Bus::assertDispatchedAfterResponseTimes(KirimPesanTelegram::class, 2);

        // 4. Data membaik, Awas tetap ditahan.
        $this->post($simulator, ['wilayah_id' => $w->id, 'kelembapan_tanah' => 20]);
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Awas');

        // 5. Admin mencabut: kembali mengikuti data.
        $this->post(route('admin.ews.awas.cabut', $w), ['alasan' => 'Lereng sudah diperiksa', 'konfirmasi' => '1']);
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Aman');

        $this->assertSame(['setujui_validasi', 'cabut_awas'], Aktivitas::query()->orderBy('id')->pluck('aksi')->all());
    }
}
