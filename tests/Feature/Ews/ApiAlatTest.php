<?php

namespace Tests\Feature\Ews;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiAlatTest extends TestCase
{
    use RefreshDatabase;

    private const KUNCI = 'kunci-uji-rahasia';

    protected function setUp(): void
    {
        parent::setUp();
        config(['ews.api_key' => self::KUNCI, 'ews.telegram.token' => null, 'ews.telegram.chat_id' => null]);
        Http::preventStrayRequests();
    }

    private function kirim(array $data, ?string $kunci = self::KUNCI)
    {
        return $this->postJson('/api/sensor', $data, $kunci === null ? [] : ['X-API-KEY' => $kunci]);
    }

    public function test_201_sesuai_kontrak(): void
    {
        $wilayah = Wilayah::factory()->create();

        $this->kirim(['wilayah_id' => $wilayah->id, 'kelembapan_tanah' => 63.4, 'curah_hujan' => 12.5, 'kemiringan' => 3.2, 'kelembapan_udara' => 88])
            ->assertCreated()
            ->assertJsonStructure(['pesan', 'menunggu_validasi', 'telegram_terkirim', 'waktu_server', 'data' => ['id', 'status', 'warna']])
            ->assertJson(['pesan' => 'Data tersimpan', 'menunggu_validasi' => false, 'telegram_terkirim' => null, 'data' => ['status' => 'Waspada', 'warna' => 'kuning']]);

        $this->assertDatabaseHas('ews_pembacaan', ['wilayah_id' => $wilayah->id, 'kelembapan_tanah' => 63.4, 'pemicu' => 'Kelembapan tanah 63,4%, Curah hujan 12,5 mm/jam']);
    }

    public function test_format_persis_kiriman_firmware_contoh(): void
    {
        $wilayah = Wilayah::factory()->create();
        // esp32_contoh.ino merakit JSON sebagai teks: tanpa spasi, angka dua desimal, field hilang bila gagal baca.
        $json = '{"wilayah_id":'.$wilayah->id.',"curah_hujan":0.00,"kemiringan":-1.25}';

        $this->call('POST', '/api/sensor', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_API_KEY' => self::KUNCI,
        ], $json)->assertCreated()->assertJsonPath('data.status', 'Aman');

        $pembacaan = Pembacaan::query()->sole();
        $this->assertSame(0.0, $pembacaan->curah_hujan);
        $this->assertSame(-1.25, $pembacaan->kemiringan);
        $this->assertNull($pembacaan->kelembapan_tanah);
    }

    public function test_nama_lokasi_diterima_sebagai_ganti_id(): void
    {
        $wilayah = Wilayah::factory()->create(['nama' => 'Lereng Uji']);

        $this->kirim(['lokasi' => 'Lereng Uji', 'curah_hujan' => 1])->assertCreated();

        $this->assertSame($wilayah->id, Pembacaan::query()->sole()->wilayah_id);
    }

    public function test_alias_lama_kelembapan_diterima(): void
    {
        $wilayah = Wilayah::factory()->create();

        $this->kirim(['wilayah_id' => $wilayah->id, 'kelembapan' => 42])->assertCreated();

        $this->assertSame(42.0, Pembacaan::query()->sole()->kelembapan_tanah);
    }

    public function test_nama_baru_menang_atas_alias_lama(): void
    {
        $wilayah = Wilayah::factory()->create();

        $this->kirim(['wilayah_id' => $wilayah->id, 'kelembapan' => 42, 'kelembapan_tanah' => 43])->assertCreated();

        $this->assertSame(43.0, Pembacaan::query()->sole()->kelembapan_tanah);
    }

    public function test_sensor_null_tersimpan_sebagai_null_bukan_nol(): void
    {
        $wilayah = Wilayah::factory()->create();

        $this->kirim(['wilayah_id' => $wilayah->id, 'kelembapan_tanah' => null, 'curah_hujan' => 3])->assertCreated();

        $pembacaan = Pembacaan::query()->sole();
        $this->assertNull($pembacaan->kelembapan_tanah);
        $this->assertNull($pembacaan->kemiringan);
        $this->assertNull($pembacaan->kelembapan_udara);
    }

    #[DataProvider('kunciDitolak')]
    public function test_401_kunci_salah_atau_kosong(?string $kunci): void
    {
        $wilayah = Wilayah::factory()->create();

        $this->kirim(['wilayah_id' => $wilayah->id, 'curah_hujan' => 1], $kunci)->assertUnauthorized();
        $this->getJson('/api/ping', $kunci === null ? [] : ['X-API-KEY' => $kunci])->assertUnauthorized();
        $this->assertDatabaseCount('ews_pembacaan', 0);
    }

    public static function kunciDitolak(): array
    {
        return ['salah' => ['kunci-salah'], 'kosong' => [''], 'tanpa header' => [null]];
    }

    public function test_401_jika_kunci_server_belum_diisi(): void
    {
        config(['ews.api_key' => '']);

        $this->kirim(['wilayah_id' => 1, 'curah_hujan' => 1], '')->assertUnauthorized();
    }

    #[DataProvider('dataTidakValid')]
    public function test_422_data_tidak_valid(array $tambahan, string $kolom): void
    {
        $wilayah = Wilayah::factory()->create();
        $data = ['wilayah_id' => $wilayah->id, ...$tambahan];

        $this->kirim(array_filter($data, fn ($v) => $v !== '__hapus'))
            ->assertUnprocessable()
            ->assertJsonPath('pesan', 'Data tidak valid')
            ->assertJsonValidationErrors([$kolom], 'errors');

        $this->assertDatabaseCount('ews_pembacaan', 0);
    }

    public static function dataTidakValid(): array
    {
        return [
            'tanpa sensor' => [[], 'kelembapan_tanah'],
            'semua sensor null' => [['curah_hujan' => null], 'kelembapan_tanah'],
            'kabel false saja' => [['kabel_putus' => false], 'kelembapan_tanah'],
            'tanah di atas 100' => [['kelembapan_tanah' => 100.1], 'kelembapan_tanah'],
            'tanah negatif' => [['kelembapan_tanah' => -1], 'kelembapan_tanah'],
            'hujan negatif' => [['curah_hujan' => -0.1], 'curah_hujan'],
            'hujan di atas 1000' => [['curah_hujan' => 1000.5], 'curah_hujan'],
            'miring di atas 90' => [['kemiringan' => 91], 'kemiringan'],
            'miring di bawah -90' => [['kemiringan' => -91], 'kemiringan'],
            'udara di atas 100' => [['kelembapan_udara' => 101], 'kelembapan_udara'],
            'bukan angka' => [['curah_hujan' => 'deras'], 'curah_hujan'],
            'kabel bukan boolean' => [['curah_hujan' => 1, 'kabel_putus' => 'mungkin'], 'kabel_putus'],
            'wilayah tidak dikenal' => [['wilayah_id' => 99999, 'curah_hujan' => 1], 'wilayah_id'],
            'tanpa wilayah' => [['wilayah_id' => '__hapus', 'curah_hujan' => 1], 'wilayah_id'],
        ];
    }

    public function test_wilayah_terhapus_ditolak_dan_riwayat_tetap_ada(): void
    {
        $wilayah = Wilayah::factory()->create();
        Pembacaan::factory()->create(['wilayah_id' => $wilayah->id]);
        $wilayah->delete();

        $this->kirim(['wilayah_id' => $wilayah->id, 'curah_hujan' => 1])->assertUnprocessable();

        $this->assertDatabaseCount('ews_pembacaan', 1);
        $this->assertSame($wilayah->nama, Pembacaan::query()->with('wilayah')->sole()->wilayah->nama);
    }

    public function test_kabel_putus_langsung_awas_dan_ditahan(): void
    {
        $wilayah = Wilayah::factory()->create();

        $this->kirim(['wilayah_id' => $wilayah->id, 'kabel_putus' => true])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Awas')
            ->assertJsonPath('data.warna', 'merah')
            ->assertJsonPath('menunggu_validasi', false);

        $wilayah->refresh();
        $this->assertTrue($wilayah->status_awas);
        $this->assertSame('Kabel EWS putus (otomatis)', $wilayah->awas_sumber);
        $this->assertNotNull($wilayah->awas_sejak);
    }

    public function test_ping_mengembalikan_jam_dan_daftar_wilayah_aktif(): void
    {
        $aktif = Wilayah::factory()->create(['nama' => 'Aktif']);
        Wilayah::factory()->create(['nama' => 'Terhapus'])->delete();

        $this->getJson('/api/ping', ['X-API-KEY' => self::KUNCI])
            ->assertOk()
            ->assertJsonPath('pesan', 'OK')
            ->assertJsonPath('wilayah', [['id' => $aktif->id, 'nama' => 'Aktif']])
            ->assertJsonStructure(['waktu_server']);
    }

    public function test_throttle_60_per_menit(): void
    {
        foreach (range(1, 60) as $i) {
            $this->getJson('/api/ping', ['X-API-KEY' => 'salah'])->assertUnauthorized();
        }

        $this->getJson('/api/ping', ['X-API-KEY' => 'salah'])->assertTooManyRequests();
    }
}
