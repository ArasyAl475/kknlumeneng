<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PencatatanAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function dataKopi(array $ubah = []): array
    {
        return $ubah + [
            'tanggal_panen' => '2026-10-05',
            'jenis_kopi' => 'Robusta',
            'tempat_panen' => 'Kebun Krajan',
            'jumlah_kg' => '100.50',
            'limbah_kg' => '20.25',
        ];
    }

    public function test_semua_halaman_admin_terbuka_saat_ada_data(): void
    {
        $kopi = PanenKopi::query()->create($this->dataKopi());
        $jamur = PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 5, 'dengan_cangkang_kopi' => true]);
        $jual = PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-05', 'jumlah_kg' => 2, 'total_rupiah' => 50000]);

        $this->get(route('admin.toko.panen-kopi.index'))->assertOk()->assertSee('Senin, 05-10-2026')->assertSee('100,50 kg');
        $this->get(route('admin.toko.panen-kopi.create'))->assertOk();
        $this->get(route('admin.toko.panen-kopi.edit', $kopi))->assertOk()->assertSee('Kebun Krajan');
        $this->get(route('admin.toko.panen-jamur.index'))->assertOk()->assertSee('Dengan cangkang kopi');
        $this->get(route('admin.toko.panen-jamur.create'))->assertOk()->assertDontSee('<option value="0" selected', false)->assertDontSee('<option value="1" selected', false);
        $this->get(route('admin.toko.panen-jamur.edit', $jamur))->assertOk()->assertSee('<option value="1" selected', false);
        $this->get(route('admin.toko.penjualan-jamur.index'))->assertOk()->assertSee('Rp 50.000');
        $this->get(route('admin.toko.penjualan-jamur.create'))->assertOk();
        $this->get(route('admin.toko.penjualan-jamur.edit', $jual))->assertOk();
    }

    public function test_halaman_kosong_memberi_petunjuk(): void
    {
        $this->get(route('admin.toko.panen-kopi.index'))->assertOk()->assertSee('Belum ada catatan panen kopi');
        $this->get(route('admin.toko.panen-jamur.index'))->assertOk()->assertSee('Belum ada catatan panen jamur');
        $this->get(route('admin.toko.penjualan-jamur.index'))->assertOk()->assertSee('Belum ada catatan penjualan jamur');
    }

    public function test_panen_kopi_disimpan_dengan_tulisan_seragam(): void
    {
        $this->post(route('admin.toko.panen-kopi.store'), $this->dataKopi(['jenis_kopi' => '  robusta ', 'tempat_panen' => 'kebun   krajan']))
            ->assertRedirect(route('admin.toko.panen-kopi.index'));

        $this->assertDatabaseHas('toko_panen_kopi', ['jenis_kopi' => 'Robusta', 'tempat_panen' => 'Kebun Krajan']);
    }

    public function test_panen_kopi_bisa_diubah_dan_dihapus(): void
    {
        $panen = PanenKopi::query()->create($this->dataKopi());

        $this->put(route('admin.toko.panen-kopi.update', $panen), $this->dataKopi(['jumlah_kg' => '75']))->assertRedirect();
        $this->assertSame('75.00', $panen->fresh()->jumlah_kg);

        $this->delete(route('admin.toko.panen-kopi.destroy', $panen))->assertRedirect();
        $this->assertDatabaseCount('toko_panen_kopi', 0);
    }

    public function test_panen_kopi_menolak_isian_salah(): void
    {
        $this->post(route('admin.toko.panen-kopi.store'), [
            'tanggal_panen' => now()->addDay()->format('Y-m-d'),
            'jenis_kopi' => '',
            'tempat_panen' => '',
            'jumlah_kg' => '0',
            'limbah_kg' => '-1',
        ])->assertSessionHasErrors(['tanggal_panen', 'jenis_kopi', 'tempat_panen', 'jumlah_kg', 'limbah_kg']);

        $this->post(route('admin.toko.panen-kopi.store'), $this->dataKopi(['jumlah_kg' => '1.234']))
            ->assertSessionHasErrors('jumlah_kg');

        $this->assertDatabaseCount('toko_panen_kopi', 0);
    }

    public function test_pesan_galat_desimal_mudah_dibaca(): void
    {
        $this->post(route('admin.toko.panen-kopi.store'), $this->dataKopi(['jumlah_kg' => '1.234']))
            ->assertSessionHasErrors(['jumlah_kg' => 'Jumlah panen paling banyak memakai 2 angka di belakang koma.']);
    }

    public function test_limbah_nol_diterima(): void
    {
        $this->post(route('admin.toko.panen-kopi.store'), $this->dataKopi(['limbah_kg' => '0']))->assertSessionHasNoErrors();
    }

    public function test_panen_jamur_mencatat_media_tanam(): void
    {
        $this->post(route('admin.toko.panen-jamur.store'), ['tanggal_panen' => '2026-10-05', 'jumlah_kg' => '5', 'jumlah_baglog' => '40', 'dengan_cangkang_kopi' => '1'])
            ->assertRedirect(route('admin.toko.panen-jamur.index'));
        $panen = PanenJamur::query()->sole();
        $this->assertTrue($panen->dengan_cangkang_kopi);

        $this->put(route('admin.toko.panen-jamur.update', $panen), ['tanggal_panen' => '2026-10-05', 'jumlah_kg' => '5', 'jumlah_baglog' => '40', 'dengan_cangkang_kopi' => '0'])->assertRedirect();
        $this->assertFalse($panen->fresh()->dengan_cangkang_kopi);

        $this->post(route('admin.toko.panen-jamur.store'), ['tanggal_panen' => '2026-10-05', 'jumlah_kg' => '5', 'jumlah_baglog' => '40'])
            ->assertSessionHasErrors('dengan_cangkang_kopi');

        $this->delete(route('admin.toko.panen-jamur.destroy', $panen))->assertRedirect();
        $this->assertDatabaseCount('toko_panen_jamur', 0);
    }

    public function test_baglog_dan_media_penjualan_wajib_diisi(): void
    {
        $this->post(route('admin.toko.panen-jamur.store'), ['tanggal_panen' => '2026-10-05', 'jumlah_kg' => '5', 'dengan_cangkang_kopi' => '1'])
            ->assertSessionHasErrors('jumlah_baglog');
        $this->post(route('admin.toko.penjualan-jamur.store'), ['tanggal_penjualan' => '2026-10-05', 'jumlah_kg' => '2', 'total_rupiah' => '1000'])
            ->assertSessionHasErrors('dengan_cangkang_kopi');
    }

    public function test_harga_hanya_terlihat_di_halaman_admin(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 30, 'jumlah_baglog' => 100, 'biaya_baglog_rupiah' => 500000, 'dengan_cangkang_kopi' => true]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-08', 'jumlah_kg' => 2, 'dengan_cangkang_kopi' => false, 'total_rupiah' => 90000]);

        $this->get(route('admin.toko.panen-jamur.index'))->assertOk()->assertSee('Rp 5.000')->assertSee('100 baglog');
        $this->get(route('admin.toko.penjualan-jamur.index'))->assertOk()->assertSee('Rp 45.000')->assertSee('Tanpa cangkang kopi');
    }

    public function test_catatan_lama_tanpa_baglog_dan_media_tetap_tampil(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 5, 'dengan_cangkang_kopi' => true]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-05', 'jumlah_kg' => 2, 'total_rupiah' => 1000]);

        $this->get(route('admin.toko.panen-jamur.index'))->assertOk()->assertSee('Baglog belum dicatat');
        $this->get(route('admin.toko.penjualan-jamur.index'))->assertOk()->assertSee('Media tanam belum dicatat');
    }

    public function test_penjualan_mencatat_kilogram_dan_rupiah(): void
    {
        $this->post(route('admin.toko.penjualan-jamur.store'), ['tanggal_penjualan' => '2026-10-05', 'jumlah_kg' => '2.5', 'dengan_cangkang_kopi' => '1', 'total_rupiah' => '62500'])
            ->assertRedirect(route('admin.toko.penjualan-jamur.index'));
        $jual = PenjualanJamur::query()->sole();
        $this->assertSame(62500, $jual->total_rupiah);

        $this->put(route('admin.toko.penjualan-jamur.update', $jual), ['tanggal_penjualan' => '2026-10-05', 'jumlah_kg' => '2.5', 'dengan_cangkang_kopi' => '1', 'total_rupiah' => '70000'])->assertRedirect();
        $this->assertSame(70000, $jual->fresh()->total_rupiah);

        $this->post(route('admin.toko.penjualan-jamur.store'), ['tanggal_penjualan' => '2026-10-05', 'jumlah_kg' => '2', 'dengan_cangkang_kopi' => '1', 'total_rupiah' => '100.50'])
            ->assertSessionHasErrors('total_rupiah');

        $this->delete(route('admin.toko.penjualan-jamur.destroy', $jual))->assertRedirect();
        $this->assertDatabaseCount('toko_penjualan_jamur', 0);
    }
}
