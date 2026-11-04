<?php

namespace Tests\Feature\Ews;

use App\Jobs\Ews\KirimPesanTelegram;
use App\Models\Ews\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TelegramTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456:TOKEN-RAHASIA';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ews.api_key' => 'k',
            'ews.telegram.token' => self::TOKEN,
            'ews.telegram.chat_id' => '-100',
            'ews.telegram.cooldown_menit' => 30,
        ]);
    }

    private function kirim(Wilayah $wilayah, array $data = ['kelembapan_tanah' => 90])
    {
        return $this->postJson('/api/sensor', ['wilayah_id' => $wilayah->id, ...$data], ['X-API-KEY' => 'k']);
    }

    public function test_pesan_perlu_validasi_berisi_lokasi_sensor_pemicu_dan_tautan(): void
    {
        Http::fake();
        $wilayah = Wilayah::factory()->create(['nama' => 'Lereng Uji']);

        $this->kirim($wilayah, ['kelembapan_tanah' => 90, 'kelembapan_udara' => 88.5])->assertCreated();

        Http::assertSentCount(1);
        Http::assertSent(function ($permintaan) {
            $teks = $permintaan['text'];

            return str_contains($permintaan->url(), '/sendMessage')
                && $permintaan['chat_id'] === '-100'
                && str_contains($teks, 'PERLU VALIDASI ADMIN')
                && str_contains($teks, 'Lokasi: Lereng Uji')
                && str_contains($teks, 'Kelembapan udara 88,5%')
                && str_contains($teks, 'Pemicu: Kelembapan tanah 90%')
                && str_contains($teks, 'WIB')
                && str_contains($teks, '/admin/ews/validasi');
        });
    }

    public function test_kabel_putus_hanya_terkirim_sekali(): void
    {
        Bus::fake();
        $wilayah = Wilayah::factory()->create();

        $this->kirim($wilayah, ['kabel_putus' => true])->assertCreated();
        $this->kirim($wilayah, ['kabel_putus' => true])->assertCreated();

        Bus::assertDispatchedAfterResponseTimes(KirimPesanTelegram::class, 1);
        Bus::assertDispatchedAfterResponse(KirimPesanTelegram::class, fn ($job) => str_contains($job->pesan, 'STATUS AWAS (otomatis)'));
    }

    public function test_cooldown_menahan_pesan_validasi_per_wilayah(): void
    {
        Bus::fake();
        config(['ews.validasi_jeda_menit' => 0]);
        $wilayah = Wilayah::factory()->create();

        $this->kirim($wilayah)->assertCreated();
        $wilayah->pembacaan()->update(['validasi' => 'ditolak', 'divalidasi_pada' => now()->subDay()]);
        $this->kirim($wilayah)->assertJsonPath('menunggu_validasi', true);
        Bus::assertDispatchedAfterResponseTimes(KirimPesanTelegram::class, 1);

        $this->kirim(Wilayah::factory()->create())->assertCreated();
        Bus::assertDispatchedAfterResponseTimes(KirimPesanTelegram::class, 2);
    }

    public function test_gagal_kirim_tidak_menggagalkan_api_dan_token_tidak_masuk_log(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28 untuk https://api.telegram.org/bot'.self::TOKEN.'/sendMessage'));
        Log::spy();

        $this->kirim(Wilayah::factory()->create())->assertCreated();

        $this->assertDatabaseCount('ews_pembacaan', 1);
        Log::shouldHaveReceived('error')->withArgs(fn (string $pesan) => ! str_contains($pesan, self::TOKEN) && str_contains($pesan, '***'))->once();
    }

    public function test_respons_ditolak_telegram_tidak_menggagalkan_api(): void
    {
        Http::fake(['*' => Http::response(['ok' => false], 403)]);
        Log::spy();

        $this->kirim(Wilayah::factory()->create())->assertCreated();

        Log::shouldHaveReceived('error')->withArgs(fn (string $pesan, array $konteks) => $konteks === ['status' => 403])->once();
    }

    public function test_tanpa_konfigurasi_tidak_memanggil_telegram(): void
    {
        Http::fake();
        config(['ews.telegram.token' => null]);

        $this->kirim(Wilayah::factory()->create())->assertCreated();

        Http::assertNothingSent();
    }
}
