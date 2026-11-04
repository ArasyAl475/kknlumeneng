<?php

namespace Tests\Feature\Ews;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use App\Services\Ews\DataDasbor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DasborTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ews.api_key' => 'k', 'ews.telegram.token' => null]);
        Http::preventStrayRequests();
    }

    public function test_halaman_tampil_dengan_teks_kunci_dan_disclaimer(): void
    {
        Pembacaan::factory()->create();

        $this->get('/ews')->assertOk()
            ->assertSee('Status desa saat ini')
            ->assertSee('Aman')
            ->assertSee('bukan peringatan resmi BPBD')
            ->assertSee('Kejadian terbaru')
            ->assertSee('aria-live="polite"', false);
    }

    public function test_keadaan_kosong_tanpa_wilayah(): void
    {
        $this->get('/ews')->assertOk()->assertSee('Belum ada data')->assertSee('Belum ada wilayah yang dipantau');
        $this->getJson('/api/ews/data')->assertOk()->assertJsonPath('utama.status', null)->assertJsonPath('utama.warna', 'kosong');
    }

    public function test_tidak_memuat_skrip_atau_gaya_dari_cdn(): void
    {
        $html = $this->get('/ews')->getContent();

        $this->assertDoesNotMatchRegularExpression('/<(script|link)[^>]+(src|href)="https?:\/\/(?!localhost)/i', $html);
    }

    public function test_struktur_data_dan_sensor_kosong_bukan_nol(): void
    {
        $wilayah = Wilayah::factory()->create(['nama' => 'Lereng Uji']);
        Pembacaan::factory()->create(['wilayah_id' => $wilayah->id, 'kelembapan_tanah' => 63.4, 'curah_hujan' => null, 'kemiringan' => -7.5, 'kelembapan_udara' => 88]);

        $d = $this->getJson('/api/ews/data')->assertOk()->assertJsonStructure([
            'utama' => ['status', 'warna', 'lokasi', 'pemicu', 'waktu', 'saran'],
            'wilayah' => [['id', 'nama', 'poligon', 'status', 'warna', 'waktu', 'offline', 'sensor']],
            'offline', 'log', 'ambang',
        ])->json('wilayah.0.sensor');

        $this->assertSame('63,4%', $d['kelembapan_tanah']['teks']);
        $this->assertSame('Waspada', $d['kelembapan_tanah']['kata']);
        $this->assertNull($d['curah_hujan']['nilai']);
        $this->assertSame('Tidak ada data', $d['curah_hujan']['teks']);
        $this->assertSame('Tidak ada data', $d['curah_hujan']['kata']);
        $this->assertSame('7,5°', $d['kemiringan']['teks'], 'kemiringan tampil mutlak');
        $this->assertSame('Hanya informasi', $d['kelembapan_udara']['kata']);
    }

    public function test_level_lewat_batas_tampil(): void
    {
        Pembacaan::factory()->create(['kelembapan_tanah' => 90]);

        $this->getJson('/api/ews/data')->assertJsonPath('wilayah.0.sensor.kelembapan_tanah.kata', 'Lewat batas');
    }

    public function test_status_utama_adalah_yang_terburuk(): void
    {
        Pembacaan::factory()->create();
        Pembacaan::factory()->create(['status' => 'Waspada', 'warna' => 'kuning', 'pemicu' => 'Curah hujan 12 mm/jam', 'wilayah_id' => Wilayah::factory()->create(['nama' => 'Terburuk'])]);
        Pembacaan::factory()->create();

        $this->getJson('/api/ews/data')
            ->assertJsonPath('utama.status', 'Waspada')
            ->assertJsonPath('utama.lokasi', 'Terburuk')
            ->assertJsonPath('utama.pemicu', 'Curah hujan 12 mm/jam');
    }

    public function test_awas_ditahan_walau_data_terbaru_normal(): void
    {
        $wilayah = Wilayah::factory()->awas('Kabel EWS putus (otomatis)')->create();
        Pembacaan::factory()->create(['wilayah_id' => $wilayah->id]);

        $this->getJson('/api/ews/data')
            ->assertJsonPath('utama.status', 'Awas')
            ->assertJsonPath('utama.warna', 'merah')
            ->assertJsonPath('utama.pemicu', 'Kabel EWS putus (otomatis)');
    }

    public function test_wilayah_terhapus_tidak_tampil(): void
    {
        $wilayah = Wilayah::factory()->create();
        Pembacaan::factory()->create(['wilayah_id' => $wilayah->id]);
        $wilayah->delete();
        DataDasbor::lupakan();

        $this->getJson('/api/ews/data')->assertJsonCount(0, 'wilayah');
    }

    public function test_alat_offline_ditandai_dan_tampil_di_banner(): void
    {
        config(['ews.alat_offline_menit' => 15]);
        $wilayah = Wilayah::factory()->create(['nama' => 'Lereng Sunyi']);
        Pembacaan::factory()->create(['wilayah_id' => $wilayah->id]);
        Wilayah::factory()->create(['nama' => 'Belum Pernah']);

        $this->travel(16)->minutes();

        $this->getJson('/api/ews/data')
            ->assertJsonPath('wilayah.0.offline', true)
            ->assertJsonPath('wilayah.1.offline', false)
            ->assertJsonPath('offline.0.nama', 'Lereng Sunyi');
        $this->get('/ews')->assertSee('Alat tidak mengirim data: Lereng Sunyi');
    }

    public function test_jumlah_query_tidak_bertambah_mengikuti_jumlah_wilayah(): void
    {
        $hitung = function () {
            DataDasbor::lupakan();
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/ews/data')->assertOk();

            return count(DB::getQueryLog());
        };

        Pembacaan::factory()->count(2)->create();
        $sedikit = $hitung();

        Pembacaan::factory()->count(20)->create();
        $this->assertSame($sedikit, $hitung());
    }

    public function test_panggilan_berulang_memakai_cache_tanpa_query(): void
    {
        Pembacaan::factory()->create();
        $this->getJson('/api/ews/data')->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/api/ews/data')->assertOk();

        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_data_baru_membatalkan_cache(): void
    {
        $wilayah = Wilayah::factory()->create();
        Pembacaan::factory()->create(['wilayah_id' => $wilayah->id]);
        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Aman');

        $this->postJson('/api/sensor', ['wilayah_id' => $wilayah->id, 'kelembapan_tanah' => 60], ['X-API-KEY' => 'k'])->assertCreated();

        $this->getJson('/api/ews/data')->assertJsonPath('utama.status', 'Waspada');
    }

    public function test_etag_menghasilkan_304_dan_tanpa_cookie_sesi(): void
    {
        Pembacaan::factory()->create();

        $pertama = $this->getJson('/api/ews/data')->assertOk();
        $etag = $pertama->headers->get('ETag');
        $this->assertNotEmpty($etag);
        $this->assertEmpty($pertama->headers->getCookies(), 'endpoint berkala tidak boleh membuat sesi');
        $this->assertStringContainsString('max-age=5', $pertama->headers->get('Cache-Control'));

        $kedua = $this->getJson('/api/ews/data', ['If-None-Match' => $etag])->assertStatus(304);
        $this->assertSame('', $kedua->getContent());
    }

    public function test_log_hanya_mencatat_perubahan_status(): void
    {
        $wilayah = Wilayah::factory()->create(['nama' => 'Lereng Uji']);
        $buat = fn (string $status, string $warna, ?string $pemicu = null) => Pembacaan::factory()->create(['wilayah_id' => $wilayah->id, 'status' => $status, 'warna' => $warna, 'pemicu' => $pemicu]);

        $buat('Aman', 'hijau');
        $buat('Waspada', 'kuning', 'Curah hujan 12 mm/jam');
        $buat('Waspada', 'kuning', 'Curah hujan 13 mm/jam');
        $buat('Aman', 'hijau');

        $log = $this->getJson('/api/ews/data')->assertJsonCount(2, 'log')->json('log');
        $this->assertSame('Aman', $log[0]['status']);
        $this->assertSame('Kembali normal', $log[0]['keterangan']);
        $this->assertSame('Curah hujan 12 mm/jam', $log[1]['keterangan']);
    }

    public function test_bacaan_pertama_aman_bukan_kejadian_walau_wilayah_lain_punya_kejadian(): void
    {
        Pembacaan::factory()->create(['status' => 'Waspada', 'warna' => 'kuning', 'pemicu' => 'Curah hujan 12 mm/jam']);
        Pembacaan::factory()->create();

        $this->getJson('/api/ews/data')->assertJsonCount(1, 'log')->assertJsonPath('log.0.status', 'Waspada');
    }

    public function test_log_urut_menurut_waktu_bukan_urutan_tulis(): void
    {
        $wilayah = Wilayah::factory()->create();
        $buat = fn (int $jamLalu, string $pemicu) => (new Pembacaan)->forceFill(['wilayah_id' => $wilayah->id, 'status' => $pemicu === 'baru' ? 'Waspada' : 'Aman', 'warna' => $pemicu === 'baru' ? 'kuning' : 'hijau', 'pemicu' => $pemicu, 'created_at' => now()->subHours($jamLalu)])->save();

        // Ditulis terbalik: yang baru lebih dulu masuk basis data.
        $buat(1, 'baru');
        $buat(5, 'lama');

        $this->getJson('/api/ews/data')->assertJsonCount(1, 'log')->assertJsonPath('log.0.keterangan', 'baru');
    }

    public function test_grafik_mengembalikan_garis_ambang_dan_nilai_tertinggi_per_kelompok(): void
    {
        $wilayah = Wilayah::factory()->create();
        $buat = fn (int $menitLalu, ?float $hujan) => (new Pembacaan)->forceFill(['wilayah_id' => $wilayah->id, 'status' => 'Aman', 'warna' => 'hijau', 'curah_hujan' => $hujan, 'created_at' => now()->startOfHour()->subMinutes($menitLalu)])->save();

        // Dalam satu kelompok 10 menit yang sama: puncak 18 tidak boleh hilang.
        $buat(31, 2);
        $buat(32, 18);
        $buat(33, 3);
        $buat(60 * 25, 99);   // di luar rentang
        $buat(5, null);       // sensor kosong dilewati

        $d = $this->getJson("/api/ews/grafik?wilayah={$wilayah->id}&sensor=curah_hujan&rentang=24j")->assertOk()->json();

        $this->assertSame([18], array_map(fn ($t) => (int) $t['y'], $d['titik']));
        $this->assertEquals(['waspada' => 10, 'validasi' => 20], $d['ambang']);
        $this->assertSame('mm/jam', $d['satuan']);
    }

    public function test_grafik_kemiringan_mutlak_udara_tanpa_ambang(): void
    {
        $wilayah = Wilayah::factory()->create();
        Pembacaan::factory()->create(['wilayah_id' => $wilayah->id, 'kemiringan' => -6.5]);

        $this->assertSame(6.5, (float) $this->getJson("/api/ews/grafik?wilayah={$wilayah->id}&sensor=kemiringan&rentang=1j")->json('titik.0.y'));
        $this->assertNull($this->getJson("/api/ews/grafik?wilayah={$wilayah->id}&sensor=kelembapan_udara&rentang=1j")->json('ambang'));
    }

    public function test_grafik_menandai_celah_kiriman_dengan_null(): void
    {
        $wilayah = Wilayah::factory()->create();
        foreach ([300, 60] as $menit) {
            (new Pembacaan)->forceFill(['wilayah_id' => $wilayah->id, 'status' => 'Aman', 'warna' => 'hijau', 'curah_hujan' => 1, 'created_at' => now()->subMinutes($menit)])->save();
        }

        $titik = $this->getJson("/api/ews/grafik?wilayah={$wilayah->id}&sensor=curah_hujan&rentang=24j")->json('titik');

        $this->assertCount(3, $titik);
        $this->assertNull($titik[1]['y']);
    }

    public function test_grafik_menolak_parameter_tidak_valid(): void
    {
        $wilayah = Wilayah::factory()->create();

        $this->getJson("/api/ews/grafik?wilayah={$wilayah->id}&sensor=password&rentang=24j")->assertUnprocessable();
        $this->getJson("/api/ews/grafik?wilayah={$wilayah->id}&sensor=curah_hujan&rentang=1tahun")->assertUnprocessable();
        $this->getJson('/api/ews/grafik?wilayah=99999&sensor=curah_hujan&rentang=24j')->assertUnprocessable();
    }

    public function test_nama_wilayah_di_halaman_tetap_diamankan(): void
    {
        Pembacaan::factory()->create(['wilayah_id' => Wilayah::factory()->create(['nama' => '<script>alert(1)</script>'])]);

        $this->get('/ews')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
