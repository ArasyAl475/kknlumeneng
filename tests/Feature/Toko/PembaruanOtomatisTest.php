<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembaruanOtomatisTest extends TestCase
{
    use RefreshDatabase;

    private function versi(): string
    {
        // Lewati masa cache sidik (beberapa detik), seperti jeda antar-pemeriksaan di browser.
        $this->travel(5)->seconds();

        return $this->getJson(route('toko.hasil.versi'))->assertOk()->json('versi');
    }

    public function test_versi_berubah_saat_catatan_dibuat_diubah_dan_dihapus(): void
    {
        $kosong = $this->versi();

        $kopi = PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 10, 'limbah_kg' => 2]);
        $dibuat = $this->versi();
        $this->assertNotSame($kosong, $dibuat);

        $kopi->update(['jumlah_kg' => 12]);
        $diubah = $this->versi();
        $this->assertNotSame($dibuat, $diubah);

        $kopi->delete();
        $this->assertNotSame($diubah, $this->versi());
    }

    public function test_versi_ikut_berubah_untuk_jamur_dan_penjualan(): void
    {
        $awal = $this->versi();
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 5, 'dengan_cangkang_kopi' => true]);
        $jamur = $this->versi();
        $this->assertNotSame($awal, $jamur);

        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-05', 'jumlah_kg' => 2, 'total_rupiah' => 50000]);
        $this->assertNotSame($jamur, $this->versi());
    }

    public function test_versi_tetap_bila_tidak_ada_perubahan(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 10, 'limbah_kg' => 2]);

        $this->assertSame($this->versi(), $this->versi());
    }

    public function test_endpoint_versi_tanpa_sesi_dan_tidak_disimpan_browser(): void
    {
        $respons = $this->getJson(route('toko.hasil.versi'))->assertOk()->assertJsonStructure(['versi']);

        $respons->assertCookieMissing(config('session.cookie'));
        $this->assertStringContainsString('no-store', (string) $respons->headers->get('Cache-Control'));
    }

    public function test_halaman_hasil_membawa_penanda_dengan_versi_yang_sama(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 10, 'limbah_kg' => 2]);
        $versi = $this->getJson(route('toko.hasil.versi'))->json('versi');

        $this->get('/toko/hasil')->assertOk()
            ->assertSee('data-versi="'.$versi.'"', false)
            ->assertSee('data-segar-bagian="ringkasan"', false)
            ->assertSee('data-segar-bagian="timbang-baris"', false);
    }

    public function test_daftar_admin_membawa_penanda(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 10, 'limbah_kg' => 2]);

        foreach (['panen-kopi', 'panen-jamur', 'penjualan-jamur'] as $jenis) {
            $this->get(route("admin.toko.{$jenis}.index"))->assertOk()
                ->assertSee('data-alamat="'.route('toko.hasil.versi').'"', false)
                ->assertSee('data-segar-bagian="ringkasan"', false);
        }
    }
}
