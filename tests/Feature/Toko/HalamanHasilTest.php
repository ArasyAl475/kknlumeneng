<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanHasilTest extends TestCase
{
    use RefreshDatabase;

    public function test_warga_melihat_total_tanpa_masuk(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 1250.5, 'limbah_kg' => 300]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 8.5, 'dengan_cangkang_kopi' => true]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-05', 'jumlah_kg' => 2.5, 'total_rupiah' => 1500000]);

        $this->get('/toko/hasil')->assertOk()
            ->assertSee('1.250,50 kg')
            ->assertSee('300,00 kg')
            ->assertSee('Robusta')
            ->assertSee('Krajan')
            ->assertSee('Senin, 05-10-2026')
            ->assertSee('Oktober 2026')
            ->assertSee('8,50 kg')
            ->assertSee('Panen jamur dengan cangkang kopi')
            ->assertSee('2,50 kg')
            ->assertSee('Rp 1.500.000');
    }

    public function test_rincian_tetap_ada_di_halaman_meski_tabnya_tersembunyi(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 10, 'limbah_kg' => 2]);

        $this->get('/toko/hasil')->assertOk()
            ->assertSee('id="panel-jenis"', false)
            ->assertSee('id="panel-hari"', false)
            ->assertSee('<noscript><style>[data-panel-rincian] { display: block !important; }', false)
            ->assertSee('20,0% dari panen kopi');
    }

    public function test_warga_melihat_perbandingan_baglog_tanpa_harga(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 30, 'jumlah_baglog' => 100, 'biaya_baglog_rupiah' => 777000, 'dengan_cangkang_kopi' => true]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-06', 'jumlah_kg' => 20, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => false]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-08', 'jumlah_kg' => 2, 'dengan_cangkang_kopi' => true, 'total_rupiah' => 90000]);

        $this->get('/toko/hasil')->assertOk()
            ->assertSee('Baglog jamur: dengan dan tanpa cangkang kopi')
            ->assertSee('Tanpa cangkang kopi')
            ->assertSee('100 baglog')
            ->assertSee('0,30 kg')
            ->assertSee('0,20 kg')
            ->assertDontSee('45.000')
            ->assertDontSee('777.000')
            ->assertDontSee('Biaya')
            ->assertDontSee('Harga');
    }

    public function test_keadaan_kosong_ramah(): void
    {
        $this->get('/toko/hasil')->assertOk()->assertSee('Belum ada data panen kopi')->assertSee('Rp 0');
    }

    public function test_filter_mengubah_angka_dan_tetap_terpilih(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 11, 'limbah_kg' => 1]);
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Arabika', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 22, 'limbah_kg' => 2]);

        $this->get('/toko/hasil?jenis=Arabika&dari=2026-10-01&sampai=2026-10-31')->assertOk()
            ->assertSee('22,00 kg')
            ->assertDontSee('33,00 kg')
            ->assertSee('value="2026-10-01"', false)
            ->assertSee('<option value="Arabika" selected', false);
    }

    public function test_filter_tanggal_terbalik_ditolak(): void
    {
        $this->from('/toko/hasil')->get('/toko/hasil?dari=2026-10-31&sampai=2026-10-01')
            ->assertRedirect('/toko/hasil')
            ->assertSessionHasErrors('sampai');
    }

    public function test_filter_berbentuk_daftar_ditolak_tanpa_galat_server(): void
    {
        $this->from('/toko/hasil')->get('/toko/hasil?jenis[]=Robusta')->assertRedirect('/toko/hasil');
    }

    public function test_tulisan_berbahaya_ditampilkan_sebagai_teks(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => '<script>x</script>', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 1, 'limbah_kg' => 0]);

        $this->get('/toko/hasil')->assertOk()->assertDontSee('<script>x</script>', false);
    }

    public function test_menu_toko_membuka_rekap_saat_modul_menyala(): void
    {
        config(['modul.toko.aktif' => true]);

        $this->get('/')->assertSee(route('toko.index'), false);
        $this->get('/toko')->assertRedirect('/toko/hasil');
    }

    public function test_menu_toko_tersembunyi_saat_modul_mati(): void
    {
        config(['modul.toko.aktif' => false]);

        $this->get('/')->assertSee('Segera hadir')->assertDontSee('/toko"', false);
    }
}
