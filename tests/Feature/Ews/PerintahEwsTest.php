<?php

namespace Tests\Feature\Ews;

use App\Jobs\Ews\KirimPesanTelegram;
use App\Models\Ews\Pembacaan;
use App\Models\Ews\Pengaturan;
use App\Models\Ews\Wilayah;
use Database\Seeders\EwsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PerintahEwsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_tiga_wilayah_dan_ambang_tanpa_menimpa_perubahan(): void
    {
        $this->seed(EwsSeeder::class);
        Pengaturan::simpanAmbang(['kemiringan' => ['waspada' => 3, 'validasi' => 9]]);
        $this->seed(EwsSeeder::class);

        $this->assertSame(3, Wilayah::query()->count());
        $this->assertSame(['waspada' => 3.0, 'validasi' => 9.0], Pengaturan::ambang()['kemiringan']);
        $this->assertSame(['waspada' => 50.0, 'validasi' => 80.0], Pengaturan::ambang()['kelembapan_tanah']);
    }

    public function test_simulasi_menolak_jalan_di_produksi(): void
    {
        Wilayah::factory()->create();
        $this->app['env'] = 'production';

        $this->artisan('ews:simulasi')->assertFailed();

        $this->assertDatabaseCount('ews_pembacaan', 0);
    }

    public function test_simulasi_membuat_data_realistis_per_wilayah(): void
    {
        Wilayah::factory()->count(2)->create();

        $this->artisan('ews:simulasi', ['--skenario' => 'aman'])->assertSuccessful();

        $this->assertSame(2, Pembacaan::query()->where('status', 'Aman')->count());
    }

    public function test_simulasi_skenario_kabel_dan_validasi(): void
    {
        $a = Wilayah::factory()->create();
        $b = Wilayah::factory()->create();

        $this->artisan('ews:simulasi', ['--skenario' => 'kabel', '--wilayah' => $a->id])->assertSuccessful();
        $this->artisan('ews:simulasi', ['--skenario' => 'validasi', '--wilayah' => $b->id])->assertSuccessful();

        $this->assertTrue($a->refresh()->status_awas);
        $this->assertSame(Pembacaan::VALIDASI_MENUNGGU, $b->pembacaanTerbaru()->first()->validasi);
    }

    public function test_simulasi_riwayat_tidak_membuat_laporan_validasi(): void
    {
        Http::fake();
        config(['ews.telegram.token' => 't', 'ews.telegram.chat_id' => 'c']);
        Wilayah::factory()->create();

        $this->artisan('ews:simulasi', ['--skenario' => 'aman', '--riwayat' => 2])->assertSuccessful();

        $this->assertSame(13, Pembacaan::query()->count());
        $this->assertSame(0, Pembacaan::query()->whereNotNull('validasi')->count());
        Http::assertNothingSent();
    }

    public function test_bersihkan_menghapus_data_lama_kecuali_yang_pernah_divalidasi(): void
    {
        $wilayah = Wilayah::factory()->create();
        $buat = fn (int $hari, ?string $validasi = null) => (new Pembacaan)->forceFill([
            'wilayah_id' => $wilayah->id, 'status' => 'Aman', 'warna' => 'hijau', 'validasi' => $validasi,
            'created_at' => now()->subDays($hari),
        ])->save();

        $buat(91);
        $buat(91);
        $buat(89);
        $buat(200, Pembacaan::VALIDASI_DISETUJUI);

        $this->artisan('ews:bersihkan')->assertSuccessful();

        $this->assertDatabaseCount('ews_pembacaan', 2);
    }

    public function test_bersihkan_menolak_batas_terlalu_pendek(): void
    {
        Pembacaan::factory()->create();

        $this->artisan('ews:bersihkan', ['--hari' => 1])->assertExitCode(2);

        $this->assertDatabaseCount('ews_pembacaan', 1);
    }

    public function test_cek_alat_memberi_tahu_sekali_per_kejadian(): void
    {
        Bus::fake();
        config(['ews.telegram.token' => 't', 'ews.telegram.chat_id' => 'c', 'ews.alat_offline_menit' => 15, 'ews.api_key' => 'k']);
        $wilayah = Wilayah::factory()->create(['nama' => 'Lereng Uji']);
        Wilayah::factory()->create(['nama' => 'Belum Pernah Mengirim']);
        $this->postJson('/api/sensor', ['wilayah_id' => $wilayah->id, 'curah_hujan' => 1], ['X-API-KEY' => 'k']);

        $this->travel(14)->minutes();
        $this->artisan('ews:cek-alat');
        Bus::assertNotDispatchedAfterResponse(KirimPesanTelegram::class);

        $this->travel(2)->minutes();
        $this->artisan('ews:cek-alat');
        $this->artisan('ews:cek-alat');
        Bus::assertDispatchedAfterResponseTimes(KirimPesanTelegram::class, 1);
        Bus::assertDispatchedAfterResponse(KirimPesanTelegram::class, fn ($job) => str_contains($job->pesan, 'ALAT TIDAK MENGIRIM DATA') && str_contains($job->pesan, 'Lereng Uji'));

        // Setelah kembali mengirim lalu mati lagi, pesan boleh terkirim lagi.
        $this->postJson('/api/sensor', ['wilayah_id' => $wilayah->id, 'curah_hujan' => 1], ['X-API-KEY' => 'k']);
        $this->travel(20)->minutes();
        $this->artisan('ews:cek-alat');
        Bus::assertDispatchedAfterResponseTimes(KirimPesanTelegram::class, 2);
    }
}
