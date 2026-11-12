<?php

namespace Tests\Feature\Ews\Admin;

use App\Models\Ews\Aktivitas;
use App\Models\Ews\Titik;
use App\Models\Ews\Wilayah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TitikAdminTest extends TestCase
{
    use RefreshDatabase;

    private const BARIS = "-7.185772, 109.588701\n-7.185060, 109.587750\n-7.185356, 109.587502\n-7.186111, 109.588447";

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_titik_rawan_cukup_dengan_lintang_dan_bujur(): void
    {
        $this->get(route('admin.ews.titik.create'))->assertOk()->assertSee('Titik rawan longsor');

        $this->post(route('admin.ews.titik.store'), ['nama' => 'Tebing Barat', 'jenis' => 'rawan', 'lintang' => '-7.186000', 'bujur' => '109.590000'])
            ->assertRedirect(route('admin.ews.wilayah.index'));

        $t = Titik::query()->sole();
        $this->assertSame(-7.186, $t->lintang);
        $this->assertNull($t->poligon);
        $this->assertSame('tambah_titik', Aktivitas::query()->sole()->aksi);
        $this->get(route('admin.ews.wilayah.index'))->assertSee('Tebing Barat')->assertSee('Titik rawan longsor');
    }

    public function test_ews_alarm_menerima_batas_area_satu_titik_per_baris(): void
    {
        $this->post(route('admin.ews.titik.store'), ['nama' => 'Area Alarm', 'jenis' => 'alarm', 'lintang' => '-7.185390', 'bujur' => '109.588069', 'poligon' => self::BARIS])
            ->assertSessionHasNoErrors();

        $this->assertCount(4, Titik::query()->sole()->poligon);
    }

    #[DataProvider('isianSalah')]
    public function test_isian_tidak_valid_ditolak(array $isian, string $kolom): void
    {
        $this->post(route('admin.ews.titik.store'), $isian + ['nama' => 'X', 'jenis' => 'rawan', 'lintang' => '-7.1', 'bujur' => '109.1'])
            ->assertSessionHasErrors($kolom);

        $this->assertDatabaseCount('ews_titik', 0);
    }

    public static function isianSalah(): array
    {
        return [
            'jenis tidak dikenal' => [['jenis' => 'lain'], 'jenis'],
            'lintang kosong' => [['lintang' => ''], 'lintang'],
            'lintang bukan angka' => [['lintang' => 'abc'], 'lintang'],
            'lintang lewat 90' => [['lintang' => '97.5'], 'lintang'],
            'bujur lewat 180' => [['bujur' => '181'], 'bujur'],
            'nama kosong' => [['nama' => ''], 'nama'],
            'batas area dua titik' => [['poligon' => "-7.1, 109.1\n-7.2, 109.2"], 'poligon'],
            'batas area bukan koordinat' => [['poligon' => "satu\ndua\ntiga"], 'poligon'],
        ];
    }

    public function test_nama_harus_unik_dan_boleh_sama_dengan_dirinya_sendiri(): void
    {
        $t = Titik::factory()->create(['nama' => 'Dipakai']);

        $this->post(route('admin.ews.titik.store'), ['nama' => 'Dipakai', 'jenis' => 'rawan', 'lintang' => '-7.1', 'bujur' => '109.1'])->assertSessionHasErrors('nama');
        $this->put(route('admin.ews.titik.update', $t), ['nama' => 'Dipakai', 'jenis' => 'rawan', 'lintang' => '-7.2', 'bujur' => '109.2'])->assertSessionHasNoErrors();

        $this->assertSame(-7.2, $t->refresh()->lintang);
    }

    public function test_titik_dihapus_dan_dicatat(): void
    {
        $t = Titik::factory()->create();

        $this->delete(route('admin.ews.titik.destroy', $t))->assertSessionHas('sukses');

        $this->assertModelMissing($t);
        $this->assertSame('hapus_titik', Aktivitas::query()->sole()->aksi);
    }

    public function test_titik_tidak_ikut_mengubah_status_atau_hitungan_wilayah(): void
    {
        Titik::factory()->alarm()->create();
        Titik::factory()->create();

        $this->assertSame(0, Wilayah::query()->count());
        $this->getJson('/api/ews/data')->assertOk()
            ->assertJsonPath('utama.status', null)
            ->assertJsonCount(0, 'wilayah')
            ->assertJsonCount(2, 'titik')
            ->assertJsonPath('titik.0.jenis_teks', 'EWS alarm');
    }
}
