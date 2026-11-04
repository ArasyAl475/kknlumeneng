<?php

namespace Tests\Feature\Ews;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use App\Services\Ews\PenerimaPembacaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PermintaanValidasiTest extends TestCase
{
    use RefreshDatabase;

    private const BAHAYA = ['kelembapan_tanah' => 90.0];

    protected function setUp(): void
    {
        parent::setUp();
        config(['ews.validasi_jeda_menit' => 30, 'ews.telegram.token' => null, 'ews.telegram.chat_id' => null]);
        Http::preventStrayRequests();
    }

    private function terima(Wilayah $wilayah, array $bacaan = self::BAHAYA, bool $kabel = false): Pembacaan
    {
        return app(PenerimaPembacaan::class)->terima($wilayah, $bacaan, $kabel);
    }

    private function jumlahMenunggu(): int
    {
        return Pembacaan::query()->where('validasi', Pembacaan::VALIDASI_MENUNGGU)->count();
    }

    public function test_hanya_satu_laporan_menunggu_per_wilayah(): void
    {
        $wilayah = Wilayah::factory()->create();

        $pertama = $this->terima($wilayah);
        $kedua = $this->terima($wilayah);

        $this->assertSame(Pembacaan::VALIDASI_MENUNGGU, $pertama->validasi);
        $this->assertNull($kedua->validasi);
        $this->assertSame('Waspada', $kedua->status);
        $this->assertSame(1, $this->jumlahMenunggu());
    }

    public function test_wilayah_berbeda_punya_laporan_sendiri(): void
    {
        $this->terima(Wilayah::factory()->create());
        $this->terima(Wilayah::factory()->create());

        $this->assertSame(2, $this->jumlahMenunggu());
    }

    public function test_tidak_ada_laporan_saat_wilayah_sudah_awas(): void
    {
        $this->assertNull($this->terima(Wilayah::factory()->awas()->create())->validasi);
    }

    public function test_jeda_setelah_admin_memutuskan(): void
    {
        $wilayah = Wilayah::factory()->create();
        $laporan = $this->terima($wilayah);
        $laporan->update(['validasi' => Pembacaan::VALIDASI_DITOLAK, 'divalidasi_pada' => now()]);

        $this->travel(29)->minutes();
        $this->assertNull($this->terima($wilayah)->validasi);

        $this->travel(2)->minutes();
        $this->assertSame(Pembacaan::VALIDASI_MENUNGGU, $this->terima($wilayah)->validasi);
    }

    public function test_sensor_di_bawah_batas_validasi_tidak_membuat_laporan(): void
    {
        $this->assertNull($this->terima(Wilayah::factory()->create(), ['kelembapan_tanah' => 79.99])->validasi);
    }
}
