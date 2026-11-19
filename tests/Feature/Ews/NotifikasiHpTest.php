<?php

namespace Tests\Feature\Ews;

use App\Models\Ews\Wilayah;
use App\Models\User;
use App\Services\Ews\KeputusanAdmin;
use App\Services\Ews\PenerimaPembacaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotifikasiHpTest extends TestCase
{
    use RefreshDatabase;

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
        $this->berkasKunci = tempnam(sys_get_temp_dir(), 'fcm').'.json';
        file_put_contents($this->berkasKunci, json_encode([
            'type' => 'service_account',
            'project_id' => 'ews-uji',
            'client_email' => 'push@ews-uji.iam.gserviceaccount.com',
            'private_key' => $pem,
        ]));

        config([
            'ews.api_key' => 'kunci-alat-uji',
            'ews.push.aktif' => true,
            'ews.push.kredensial' => $this->berkasKunci,
            'ews.push.topik' => 'warga-lumeneng',
            'ews.push.jeda_menit' => 10,
            'ews.push.kirim_offline' => true,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'token-uji', 'expires_in' => 3600]),
            // Balasan khusus per tes diambil dari antrean; bila kosong, Firebase menerima pesan.
            'fcm.googleapis.com/*' => fn () => array_shift($this->balasanFcm) ?? Http::response(['name' => 'projects/ews-uji/messages/1']),
        ]);
    }

    /** @var array<int, mixed> */
    private array $balasanFcm = [];

    protected function tearDown(): void
    {
        @unlink($this->berkasKunci);
        parent::tearDown();
    }

    private function baca(Wilayah $wilayah, float $tanah, bool $kabelPutus = false): void
    {
        app(PenerimaPembacaan::class)->terima($wilayah, ['kelembapan_tanah' => $tanah], $kabelPutus);
    }

    /**
     * @return array<int, array<string, mixed>> isi "message" yang dikirim ke FCM, urut
     */
    private function terkirim(): array
    {
        return Http::recorded(fn (Request $r) => str_contains($r->url(), 'fcm.googleapis.com'))
            ->map(fn (array $pasangan) => $pasangan[0]->data()['message'])
            ->values()
            ->all();
    }

    public function test_pemeriksaan_pertama_hanya_mencatat_tanpa_mengirim(): void
    {
        $wilayah = Wilayah::factory()->create();

        // Saat fitur baru dipasang, wilayah yang sedang Waspada tidak membuat semua HP berbunyi.
        $this->baca($wilayah, 63);

        $this->assertSame([], $this->terkirim());
        $this->assertSame('waspada', $wilayah->fresh()->status_notifikasi);
    }

    public function test_aman_ke_waspada_mengirim_pesan_sesuai_kontrak_aplikasi(): void
    {
        $this->travelTo(now()->setTime(20, 33));
        $wilayah = Wilayah::factory()->create(['nama' => 'EWS-02 Sikembang']);
        $this->baca($wilayah, 10);

        $this->baca($wilayah, 63.4);

        $pesan = $this->terkirim();
        $this->assertCount(1, $pesan);
        $this->assertSame([
            'topic' => 'warga-lumeneng',
            'notification' => [
                'title' => 'Waspada: EWS-02 Sikembang',
                'body' => 'Kelembapan tanah 63,4%. Siapkan diri dan pantau kabar dari petugas. (20.33 WIB)',
            ],
            'data' => ['status' => 'waspada', 'wilayah_id' => (string) $wilayah->id],
            'android' => [
                'priority' => 'high',
                'ttl' => '21600s',
                'notification' => [
                    'channel_id' => 'ews_waspada_v3',
                    'sound' => 'ews_waspada',
                    'tag' => 'ews-wilayah-'.$wilayah->id,
                    'notification_priority' => 'PRIORITY_MAX',
                    'visibility' => 'PUBLIC',
                ],
            ],
        ], $pesan[0]);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://fcm.googleapis.com/v1/projects/ews-uji/messages:send'
            && $r->hasHeader('Authorization', 'Bearer token-uji'));
    }

    public function test_status_sama_tidak_dikirim_ulang_dan_aman_tidak_dikirim(): void
    {
        $wilayah = Wilayah::factory()->create();
        $this->baca($wilayah, 10);

        $this->baca($wilayah, 60);
        $this->baca($wilayah, 65);
        $this->baca($wilayah, 70);
        $this->baca($wilayah, 10);

        $this->assertCount(1, $this->terkirim());
        $this->assertSame('aman', $wilayah->fresh()->status_notifikasi);
    }

    public function test_waspada_bolak_balik_ditahan_jeda_sepuluh_menit(): void
    {
        $wilayah = Wilayah::factory()->create();
        $this->baca($wilayah, 10);

        $this->baca($wilayah, 60);
        $this->baca($wilayah, 10);
        $this->travel(5)->minutes();
        $this->baca($wilayah, 60);

        $this->assertCount(1, $this->terkirim());

        $this->baca($wilayah, 10);
        $this->travel(6)->minutes();
        $this->baca($wilayah, 60);

        $this->assertCount(2, $this->terkirim());
    }

    public function test_kabel_putus_dari_alat_langsung_mengirim_awas(): void
    {
        $wilayah = Wilayah::factory()->create(['nama' => 'EWS-02 Sikembang']);
        $this->baca($wilayah, 10);

        $this->postJson('/api/sensor', ['wilayah_id' => $wilayah->id_alat, 'kabel_putus' => true], ['X-API-KEY' => 'kunci-alat-uji'])
            ->assertCreated();

        $pesan = $this->terkirim();
        $this->assertCount(1, $pesan);
        $this->assertSame('Awas: EWS-02 Sikembang', $pesan[0]['notification']['title']);
        $this->assertStringStartsWith('Kabel EWS putus (otomatis). Segera menjauh dari lereng', $pesan[0]['notification']['body']);
        $this->assertSame('awas', $pesan[0]['data']['status']);
        $this->assertSame('ews_awas_v3', $pesan[0]['android']['notification']['channel_id']);
        $this->assertSame('ews_awas', $pesan[0]['android']['notification']['sound']);

        // Kiriman berikutnya selama Awas ditahan web tidak membuat HP berbunyi lagi.
        $this->postJson('/api/sensor', ['wilayah_id' => $wilayah->id_alat, 'kabel_putus' => true], ['X-API-KEY' => 'kunci-alat-uji']);
        $this->baca($wilayah, 10);
        $this->assertCount(1, $this->terkirim());
    }

    public function test_awas_dari_admin_selalu_dikirim_tanpa_jeda(): void
    {
        $wilayah = Wilayah::factory()->create();
        $admin = User::factory()->admin()->create();
        $keputusan = app(KeputusanAdmin::class);
        $this->baca($wilayah, 10);

        $keputusan->nyatakanAwas($wilayah, $admin, 'retakan di lereng');
        $keputusan->cabutAwas($wilayah->fresh(), $admin, 'sudah diperiksa');
        $keputusan->nyatakanAwas($wilayah->fresh(), $admin, 'retakan bertambah');

        $judul = array_map(fn (array $p) => $p['data']['status'], $this->terkirim());
        $this->assertSame(['awas', 'awas'], $judul);
    }

    public function test_alat_berhenti_mengirim_dikabarkan_penjadwal(): void
    {
        $wilayah = Wilayah::factory()->create(['nama' => 'EWS-02 Sikembang']);
        $this->baca($wilayah, 10);

        $this->travel(config('ews.alat_offline_menit') + 1)->minutes();
        $this->artisan('ews:pantau-notifikasi')->assertSuccessful();

        $pesan = $this->terkirim();
        $this->assertCount(1, $pesan);
        $this->assertSame('EWS-02 Sikembang tidak mengirim data', $pesan[0]['notification']['title']);
        $this->assertSame('offline', $pesan[0]['data']['status']);
        $this->assertSame('ews_waspada_v3', $pesan[0]['android']['notification']['channel_id']);

        // Pemeriksaan menit berikutnya tidak mengulang.
        $this->artisan('ews:pantau-notifikasi')->assertSuccessful();
        $this->assertCount(1, $this->terkirim());
    }

    public function test_kabar_offline_bisa_dimatikan(): void
    {
        config(['ews.push.kirim_offline' => false]);
        $wilayah = Wilayah::factory()->create();
        $this->baca($wilayah, 10);

        $this->travel(config('ews.alat_offline_menit') + 1)->minutes();
        $this->artisan('ews:pantau-notifikasi')->assertSuccessful();

        $this->assertSame([], $this->terkirim());
    }

    public function test_gagal_kirim_tidak_mengganggu_alat_dan_dicoba_lagi(): void
    {
        $this->balasanFcm = [Http::response(['error' => ['message' => 'Server Firebase sibuk']], 503)];
        $wilayah = Wilayah::factory()->create();
        $this->baca($wilayah, 10);

        $this->postJson('/api/sensor', ['wilayah_id' => $wilayah->id_alat, 'kabel_putus' => true], ['X-API-KEY' => 'kunci-alat-uji'])
            ->assertCreated();
        $this->assertSame('aman', $wilayah->fresh()->status_notifikasi);

        $this->artisan('ews:pantau-notifikasi')->expectsOutput('1 notifikasi terkirim.')->assertSuccessful();
        $this->assertSame('awas', $wilayah->fresh()->status_notifikasi);
    }

    public function test_token_akses_dipakai_ulang(): void
    {
        $wilayah = Wilayah::factory()->create();
        $this->baca($wilayah, 10);
        $this->baca($wilayah, 60);
        $this->baca($wilayah, 10, true);

        $this->assertCount(2, $this->terkirim());
        Http::assertSentCount(3);
    }

    public function test_push_mati_tidak_menghubungi_firebase(): void
    {
        config(['ews.push.aktif' => false]);
        $wilayah = Wilayah::factory()->create();

        $this->baca($wilayah, 10);
        $this->baca($wilayah, 60);
        $this->artisan('ews:pantau-notifikasi')->expectsOutput('0 notifikasi terkirim.');

        Http::assertNothingSent();
        $this->assertNull($wilayah->fresh()->status_notifikasi);
    }

    public function test_perintah_uji_mengirim_pesan_bertanda_uji(): void
    {
        $wilayah = Wilayah::factory()->create(['nama' => 'EWS-02 Sikembang']);

        $this->artisan('ews:uji-notifikasi', ['status' => 'awas', '--paksa' => true])
            ->expectsOutputToContain('Terkirim ke topik "warga-lumeneng"')
            ->assertSuccessful();

        $pesan = $this->terkirim();
        $this->assertCount(1, $pesan);
        $this->assertSame('[UJI] Awas: EWS-02 Sikembang', $pesan[0]['notification']['title']);
        $this->assertSame('Ini hanya uji coba sistem, bukan peringatan sungguhan.', $pesan[0]['notification']['body']);
        $this->assertSame('ews_awas_v3', $pesan[0]['android']['notification']['channel_id']);
        $this->assertSame((string) $wilayah->id, $pesan[0]['data']['wilayah_id']);
    }

    public function test_perintah_uji_periksa_saja_tidak_mengirim_ke_hp(): void
    {
        Wilayah::factory()->create();

        $this->artisan('ews:uji-notifikasi', ['status' => 'awas', '--periksa-saja' => true])
            ->expectsOutputToContain('Kunci Firebase dan isi pesan BENAR')
            ->assertSuccessful();

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'fcm.googleapis.com') && $r->data()['validate_only'] === true);
    }

    public function test_perintah_uji_bertanya_dulu_dan_menjelaskan_bila_belum_siap(): void
    {
        Wilayah::factory()->create();

        $this->artisan('ews:uji-notifikasi', ['status' => 'waspada'])
            ->expectsConfirmation('Notifikasi UJI "waspada" untuk '.Wilayah::query()->value('nama').' akan sampai ke SEMUA HP warga. Lanjutkan?', 'no')
            ->assertSuccessful();
        $this->assertSame([], $this->terkirim());

        config(['ews.push.kredensial' => '/tidak/ada/kunci.json']);
        $this->artisan('ews:uji-notifikasi', ['status' => 'awas', '--paksa' => true])
            ->expectsOutputToContain('Berkas kunci Firebase tidak ditemukan')
            ->assertFailed();

        $this->artisan('ews:uji-notifikasi', ['status' => 'aman', '--paksa' => true])->assertExitCode(2);
    }
}
