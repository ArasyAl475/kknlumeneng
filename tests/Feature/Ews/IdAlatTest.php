<?php

namespace Tests\Feature\Ews;

use App\Models\Ews\Pembacaan;
use App\Models\Ews\Wilayah;
use App\Models\User;
use App\Services\Ews\PenerimaMqtt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** ID alat (kolom id_alat) dipisah dari id baris, supaya admin bisa menyamakannya dengan angka di ESP32. */
class IdAlatTest extends TestCase
{
    use RefreshDatabase;

    private const KUNCI = 'kunci-uji-rahasia';

    private const POLIGON = "-7.17, 109.55\n-7.17, 109.56\n-7.18, 109.56";

    protected function setUp(): void
    {
        parent::setUp();
        config(['ews.api_key' => self::KUNCI, 'ews.mqtt.topik_awalan' => 'ews', 'ews.mqtt.jeda_minimal_detik' => 0]);
        Http::preventStrayRequests();
    }

    private function kirim(int $id)
    {
        return $this->postJson('/api/sensor', ['wilayah_id' => $id, 'kemiringan' => 1.5], ['X-API-KEY' => self::KUNCI]);
    }

    private function masukAdmin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_wilayah_baru_otomatis_memakai_id_baris_sebagai_id_alat(): void
    {
        $w = Wilayah::factory()->create();

        $this->assertSame($w->id, $w->fresh()->id_alat);
    }

    public function test_id_alat_bisa_berbeda_dari_id_baris_dan_dipakai_api(): void
    {
        $w = Wilayah::factory()->create();
        $w->update(['id_alat' => 1 + $w->id]);

        $this->kirim($w->id_alat)->assertCreated();
        $this->assertDatabaseHas('ews_pembacaan', ['wilayah_id' => $w->id]);

        // Angka lama (id baris) bukan lagi ID alat, jadi ditolak.
        $this->kirim($w->id)->assertUnprocessable()->assertJsonValidationErrors('wilayah_id', 'errors');
    }

    public function test_ping_menampilkan_id_alat(): void
    {
        $w = Wilayah::factory()->create(['nama' => 'Aktif']);
        $w->update(['id_alat' => 7]);

        $this->getJson('/api/ping', ['X-API-KEY' => self::KUNCI])
            ->assertOk()
            ->assertJsonPath('wilayah', [['id' => 7, 'nama' => 'Aktif']]);
    }

    public function test_topik_mqtt_memakai_id_alat(): void
    {
        $w = Wilayah::factory()->create();
        $w->update(['id_alat' => 9]);
        $penerima = app(PenerimaMqtt::class);

        $this->assertTrue($penerima->proses('ews/9/sensor', '{"kemiringan":1.5}')['diterima']);
        $this->assertDatabaseHas('ews_pembacaan', ['wilayah_id' => $w->id]);
        $this->assertFalse($penerima->proses("ews/{$w->id}/sensor", '{"kemiringan":1.5}')['diterima']);
        $this->assertSame(1, Pembacaan::query()->count());
    }

    public function test_admin_mengganti_id_alat_dan_tercatat(): void
    {
        $this->masukAdmin();
        $w = Wilayah::factory()->create(['nama' => 'Titik Area 2']);
        $w->update(['id_alat' => 20]);

        $this->put(route('admin.ews.wilayah.update', $w), ['nama' => 'Titik Area 2', 'poligon' => self::POLIGON, 'id_alat' => 1])
            ->assertRedirect(route('admin.ews.wilayah.index'))
            ->assertSessionHas('sukses');

        $this->assertSame(1, $w->fresh()->id_alat);
        $this->assertDatabaseHas('ews_aktivitas', ['aksi' => 'ubah_id_alat']);
        $this->kirim(1)->assertCreated();
    }

    public function test_id_alat_kosong_tidak_mengubah_angka_lama(): void
    {
        $this->masukAdmin();
        $w = Wilayah::factory()->create();
        $w->update(['id_alat' => 5]);

        $this->put(route('admin.ews.wilayah.update', $w), ['nama' => $w->nama, 'poligon' => self::POLIGON])->assertSessionDoesntHaveErrors();

        $this->assertSame(5, $w->fresh()->id_alat);
    }

    public function test_id_alat_kembar_atau_bukan_angka_ditolak(): void
    {
        $this->masukAdmin();
        $a = Wilayah::factory()->create();
        $b = Wilayah::factory()->create();

        $this->put(route('admin.ews.wilayah.update', $b), ['nama' => $b->nama, 'poligon' => self::POLIGON, 'id_alat' => $a->id_alat])
            ->assertSessionHasErrors('id_alat');

        $this->post(route('admin.ews.wilayah.store'), ['nama' => 'Baru', 'poligon' => self::POLIGON, 'id_alat' => 'abc'])
            ->assertSessionHasErrors('id_alat');
    }

    public function test_tambah_wilayah_boleh_menentukan_id_alat_atau_otomatis(): void
    {
        $this->masukAdmin();

        $this->post(route('admin.ews.wilayah.store'), ['nama' => 'Dengan ID', 'poligon' => self::POLIGON, 'id_alat' => 12])->assertSessionHasNoErrors();
        $this->post(route('admin.ews.wilayah.store'), ['nama' => 'Otomatis', 'poligon' => self::POLIGON])->assertSessionHasNoErrors();

        $this->assertSame(12, Wilayah::query()->where('nama', 'Dengan ID')->value('id_alat'));
        $this->assertNotNull(Wilayah::query()->where('nama', 'Otomatis')->value('id_alat'));
    }

    public function test_hapus_melepas_id_alat_dan_pulihkan_memberi_angka_bebas(): void
    {
        $this->masukAdmin();
        $lama = Wilayah::factory()->create();
        $lama->update(['id_alat' => 1]);

        $this->delete(route('admin.ews.wilayah.destroy', $lama))->assertSessionHas('sukses');
        $this->assertNull(Wilayah::withTrashed()->find($lama->id)->id_alat);
        $this->kirim(1)->assertUnprocessable();

        // Angka 1 bebas dipakai wilayah lain, jadi pemulihan mendapat angka bebas berikutnya.
        $baru = Wilayah::factory()->create();
        $baru->update(['id_alat' => 1]);

        $this->post(route('admin.ews.wilayah.pulihkan', $lama->id))->assertSessionHas('sukses');
        $dipulihkan = $lama->fresh();
        $this->assertNotNull($dipulihkan->id_alat);
        $this->assertNotSame(1, $dipulihkan->id_alat);
    }

    public function test_daftar_dan_formulir_menampilkan_id_alat(): void
    {
        $this->masukAdmin();
        $w = Wilayah::factory()->create();
        $w->update(['id_alat' => 33]);

        $this->get(route('admin.ews.wilayah.index'))->assertSeeInOrder(['ID alat', '33']);
        $this->get(route('admin.ews.wilayah.edit', $w))->assertSee('name="id_alat"', false)->assertSee('value="33"', false);
        $this->get(route('admin.ews.wilayah.create'))->assertSee('name="id_alat"', false);
    }
}
