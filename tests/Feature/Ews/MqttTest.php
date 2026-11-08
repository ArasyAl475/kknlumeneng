<?php

namespace Tests\Feature\Ews;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use App\Services\Ews\PenerimaMqtt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MqttTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ews.mqtt.topik_awalan' => 'ews',
            'ews.mqtt.jeda_minimal_detik' => 0,
        ]);
        Http::preventStrayRequests();
    }

    private function proses(string $topik, array|string $isi, bool $retained = false): array
    {
        return app(PenerimaMqtt::class)->proses($topik, is_array($isi) ? json_encode($isi) : $isi, $retained);
    }

    public function test_pesan_valid_tersimpan_dan_dinilai(): void
    {
        $wilayah = Wilayah::factory()->create();

        $hasil = $this->proses("ews/{$wilayah->id}/sensor", ['kelembapan_tanah' => 63.4, 'intensitas_hujan' => 45.5, 'kemiringan' => 3.2, 'kelembapan_udara' => 88]);

        $this->assertTrue($hasil['diterima']);
        $this->assertSame('Waspada', $hasil['status']);
        $this->assertDatabaseHas('ews_pembacaan', ['wilayah_id' => $wilayah->id, 'kelembapan_tanah' => 63.4, 'pemicu' => 'Kelembapan tanah 63,4%, Intensitas hujan 45,5%']);
    }

    public function test_isi_persis_seperti_kiriman_firmware(): void
    {
        $wilayah = Wilayah::factory()->create();
        // Firmware merakit JSON sebagai teks, tanpa spasi, field yang gagal dibaca tidak dikirim.
        $hasil = $this->proses("ews/{$wilayah->id}/sensor", '{"intensitas_hujan":0.00,"kemiringan":-1.25}');

        $this->assertTrue($hasil['diterima']);
        $pembacaan = Pembacaan::query()->sole();
        $this->assertSame(-1.25, $pembacaan->kemiringan);
        $this->assertNull($pembacaan->kelembapan_tanah);
    }

    public function test_wilayah_id_di_isi_boleh_asal_sama_dengan_topik(): void
    {
        $wilayah = Wilayah::factory()->create();

        $this->assertTrue($this->proses("ews/{$wilayah->id}/sensor", ['wilayah_id' => $wilayah->id, 'kelembapan_tanah' => 20])['diterima']);
    }

    public function test_wilayah_id_di_isi_yang_beda_dari_topik_ditolak(): void
    {
        $a = Wilayah::factory()->create();
        $b = Wilayah::factory()->create();

        $hasil = $this->proses("ews/{$a->id}/sensor", ['wilayah_id' => $b->id, 'kelembapan_tanah' => 20]);

        $this->assertFalse($hasil['diterima']);
        $this->assertStringContainsString('tidak sama dengan topik', $hasil['alasan']);
        $this->assertDatabaseCount('ews_pembacaan', 0);
    }

    public function test_alias_lama_kelembapan_diterima(): void
    {
        $wilayah = Wilayah::factory()->create();

        $this->proses("ews/{$wilayah->id}/sensor", ['kelembapan' => 42]);

        $this->assertSame(42.0, Pembacaan::query()->sole()->kelembapan_tanah);
    }

    public function test_kabel_putus_langsung_awas_dan_ditahan(): void
    {
        $wilayah = Wilayah::factory()->create();

        $hasil = $this->proses("ews/{$wilayah->id}/sensor", ['kabel_putus' => true]);

        $this->assertSame('Awas', $hasil['status']);
        $wilayah->refresh();
        $this->assertTrue($wilayah->status_awas);
        $this->assertSame('Kabel EWS putus (otomatis)', $wilayah->awas_sumber);
    }

    #[DataProvider('topikSalah')]
    public function test_topik_tidak_dikenal_ditolak(string $topik): void
    {
        Wilayah::factory()->create();

        $hasil = $this->proses($topik, ['kelembapan_tanah' => 20]);

        $this->assertFalse($hasil['diterima']);
        $this->assertNull($hasil['wilayah_id']);
        $this->assertDatabaseCount('ews_pembacaan', 0);
    }

    public static function topikSalah(): array
    {
        return [
            'tanpa angka' => ['ews/abc/sensor'],
            'akhiran lain' => ['ews/1/status'],
            'awalan lain' => ['lain/1/sensor'],
            'terlalu dalam' => ['ews/1/x/sensor'],
            'angka negatif' => ['ews/-1/sensor'],
        ];
    }

    #[DataProvider('isiTidakValid')]
    public function test_isi_tidak_valid_ditolak_dan_tidak_disimpan(array|string $isi): void
    {
        $wilayah = Wilayah::factory()->create();

        $hasil = $this->proses("ews/{$wilayah->id}/sensor", $isi);

        $this->assertFalse($hasil['diterima']);
        $this->assertSame($wilayah->id, $hasil['wilayah_id']);
        $this->assertDatabaseCount('ews_pembacaan', 0);
    }

    public static function isiTidakValid(): array
    {
        return [
            'bukan json' => ['halo'],
            'json kosong' => ['{}'],
            'daftar bukan objek' => ['[1,2,3]'],
            'angka saja' => ['42'],
            'tanah di atas 100' => [['kelembapan_tanah' => 100.1]],
            'hujan negatif' => [['intensitas_hujan' => -1]],
            'miring di atas 90' => [['kemiringan' => 91]],
            'bukan angka' => [['kelembapan_tanah' => 'basah']],
            'kabel bukan boolean' => [['kelembapan_tanah' => 1, 'kabel_putus' => 'mungkin']],
            'semua null' => [['kelembapan_tanah' => null, 'kemiringan' => null]],
            'kabel false saja' => [['kabel_putus' => false]],
            'field lama curah_hujan saja' => [['curah_hujan' => 5]],
        ];
    }

    public function test_isi_terlalu_besar_ditolak(): void
    {
        $wilayah = Wilayah::factory()->create();

        $hasil = $this->proses("ews/{$wilayah->id}/sensor", json_encode(['kelembapan_tanah' => 1, 'sampah' => str_repeat('x', 3000)]));

        $this->assertFalse($hasil['diterima']);
        $this->assertSame('Isi terlalu besar', $hasil['alasan']);
    }

    public function test_wilayah_tidak_ada_atau_terhapus_ditolak(): void
    {
        $this->assertFalse($this->proses('ews/99999/sensor', ['kelembapan_tanah' => 20])['diterima']);

        $terhapus = Wilayah::factory()->create();
        $terhapus->delete();
        $this->assertFalse($this->proses("ews/{$terhapus->id}/sensor", ['kelembapan_tanah' => 20])['diterima']);

        $this->assertDatabaseCount('ews_pembacaan', 0);
    }

    public function test_pesan_retained_diabaikan(): void
    {
        $wilayah = Wilayah::factory()->create();

        $hasil = $this->proses("ews/{$wilayah->id}/sensor", ['kelembapan_tanah' => 20], retained: true);

        $this->assertFalse($hasil['diterima']);
        $this->assertStringContainsString('retained', $hasil['alasan']);
        $this->assertDatabaseCount('ews_pembacaan', 0);
    }

    public function test_pesan_terlalu_rapat_dari_wilayah_yang_sama_ditolak(): void
    {
        config(['ews.mqtt.jeda_minimal_detik' => 60]);
        $a = Wilayah::factory()->create();
        $b = Wilayah::factory()->create();

        $this->assertTrue($this->proses("ews/{$a->id}/sensor", ['kelembapan_tanah' => 20])['diterima']);
        $kedua = $this->proses("ews/{$a->id}/sensor", ['kelembapan_tanah' => 21]);
        $this->assertFalse($kedua['diterima']);
        $this->assertStringContainsString('Terlalu sering', $kedua['alasan']);

        // Wilayah lain tidak ikut terhambat.
        $this->assertTrue($this->proses("ews/{$b->id}/sensor", ['kelembapan_tanah' => 20])['diterima']);
        $this->assertDatabaseCount('ews_pembacaan', 2);
    }

    public function test_awalan_topik_mengikuti_pengaturan(): void
    {
        config(['ews.mqtt.topik_awalan' => '/desa/lumeneng/']);
        $wilayah = Wilayah::factory()->create();
        $penerima = app(PenerimaMqtt::class);

        $this->assertSame('desa/lumeneng/+/sensor', $penerima->polaTopikLangganan());
        $this->assertSame("desa/lumeneng/{$wilayah->id}/balasan", $penerima->topikBalasan($wilayah->id));
        $this->assertTrue($penerima->proses("desa/lumeneng/{$wilayah->id}/sensor", '{"kelembapan_tanah":20}')['diterima']);
        $this->assertFalse($penerima->proses("ews/{$wilayah->id}/sensor", '{"kelembapan_tanah":20}')['diterima']);
    }

    public function test_perintah_tidak_melakukan_apa_apa_bila_belum_diaktifkan(): void
    {
        config(['ews.mqtt.aktif' => false]);

        $this->artisan('ews:mqtt')->expectsOutputToContain('belum diaktifkan')->assertSuccessful();
    }

    public function test_perintah_gagal_bila_host_kosong(): void
    {
        config(['ews.mqtt.aktif' => true, 'ews.mqtt.host' => null]);

        $this->artisan('ews:mqtt')->expectsOutputToContain('EWS_MQTT_HOST')->assertFailed();
    }

    public function test_perintah_menolak_tanpa_tls_di_produksi(): void
    {
        config(['ews.mqtt.aktif' => true, 'ews.mqtt.host' => 'broker.contoh', 'ews.mqtt.tls' => false]);
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('ews:mqtt')->expectsOutputToContain('wajib memakai TLS')->assertFailed();
    }
}
