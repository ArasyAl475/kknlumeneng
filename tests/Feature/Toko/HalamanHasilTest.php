<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use App\Services\Toko\RekapHasil;
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
            ->assertSee('300,00')
            ->assertSee('Krajan')
            ->assertSee('05-10-2026')
            ->assertSee('8,50 kg')
            ->assertSee('100,0% memakai cangkang kopi')
            ->assertSee('2,50 kg')
            ->assertSee('Rp 1.500.000');
    }

    public function test_rincian_panen_kopi_berupa_tabel_catatan_timbang(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Blok Krajan', 'nama_petani' => 'Kelompok Tani Giri', 'jumlah_kg' => 10, 'limbah_kg' => 2.5]);
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-06', 'jenis_kopi' => 'Arabika', 'tempat_panen' => 'Blok Giri', 'jumlah_kg' => 20, 'limbah_kg' => 5]);

        $this->get('/toko/hasil')->assertOk()
            ->assertSeeInOrder(['Tanggal Timbang', 'Nama Petani/Kelompok', 'Blok Lahan', 'Berat Kotor (kg)', 'Berat Bersih (kg)'])
            ->assertSeeInOrder(['06-10-2026', 'Blok Giri', '20,00', '15,00', '05-10-2026', 'Kelompok Tani Giri', 'Blok Krajan', '10,00', '7,50'])
            ->assertSee('Jumlah seluruh catatan (2)')
            ->assertSee('30,00')
            ->assertSee('22,50')
            ->assertSee('25,0% dari panen kopi')
            ->assertDontSee('role="tablist"', false)
            ->assertDontSee('data-panel-rincian', false);
    }

    public function test_catatan_tanpa_nama_petani_tampil_strip(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Blok Krajan', 'jumlah_kg' => 10, 'limbah_kg' => 2]);

        $this->get('/toko/hasil')->assertOk()->assertSee('data-sel="Nama Petani/Kelompok">–</td>', false);
    }

    public function test_nama_petani_ditulis_aman_dan_berat_bersih_tidak_negatif(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'nama_petani' => '<b>x</b>', 'jumlah_kg' => 1, 'limbah_kg' => 3]);

        $html = $this->get('/toko/hasil')->assertOk()->getContent();

        $this->assertStringNotContainsString('<b>x</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $html);
        $this->assertStringContainsString('data-sel="Berat Bersih (kg)" class="timbang__angka">0,00</td>', $html);
    }

    public function test_tabel_timbang_dibatasi_dan_memberi_petunjuk_unduh(): void
    {
        foreach (range(1, RekapHasil::BATAS_TIMBANG + 1) as $n) {
            PanenKopi::query()->create(['tanggal_panen' => now()->subDays($n)->format('Y-m-d'), 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 1, 'limbah_kg' => 0]);
        }

        $html = $this->get('/toko/hasil')->assertOk()
            ->assertSee('Menampilkan 50 catatan terbaru dari 51')
            ->assertSee('Jumlah seluruh catatan (51)')
            ->getContent();

        $this->assertSame(RekapHasil::BATAS_TIMBANG, substr_count($html, 'class="timbang__tanggal">') - 1);
    }

    public function test_warga_melihat_perbandingan_baglog_tanpa_harga(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 30, 'jumlah_baglog' => 100, 'biaya_baglog_rupiah' => 777000, 'dengan_cangkang_kopi' => true]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-06', 'jumlah_kg' => 20, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => false]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-08', 'jumlah_kg' => 2, 'dengan_cangkang_kopi' => true, 'total_rupiah' => 90000]);

        $this->get('/toko/hasil')->assertOk()
            ->assertSee('Apakah cangkang kopi membantu jamur?')
            ->assertSee('50% lebih banyak')
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
        $this->get('/toko/hasil')->assertOk()
            ->assertSee('Belum ada data panen kopi')
            ->assertSee('Belum ada penjualan jamur')
            ->assertSee('Belum ada data untuk periode ini')
            ->assertSee('Data belum cukup untuk dibandingkan')
            ->assertDontSee('data-grafik', false);
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

    public function test_menu_toko_membuka_etalase_dan_katalog_saat_modul_menyala(): void
    {
        config(['modul.toko.aktif' => true]);

        $this->get('/')->assertSee(route('toko.index'), false);
        $this->get('/toko')->assertOk();
        $this->get('/toko/katalog')->assertOk()->assertSee('Katalog Produk Sederhana');
        $this->get('/toko/hasil')->assertOk();
    }

    public function test_empat_pilihan_grafik_punya_ikon(): void
    {
        PanenKopi::query()->create(['tanggal_panen' => '2026-10-05', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 10, 'limbah_kg' => 2]);

        $html = $this->get('/toko/hasil')->assertOk()->getContent();

        $this->assertSame(4, substr_count($html, 'ikon-seri'));
    }

    public function test_navbar_halaman_hasil_sama_dengan_halaman_lain(): void
    {
        // Dipaksa menyala agar tes tidak bergantung pada isi .env di komputer masing-masing.
        config(['modul.ews.aktif' => true, 'modul.toko.aktif' => true, 'modul.edukasi.aktif' => true, 'modul.limbah.aktif' => true]);

        $respons = $this->get('/toko/hasil')->assertOk()
            ->assertSee('data-navbar-publik', false)
            ->assertSee('History Limbah');

        foreach (['landing', 'ews.index', 'toko.index', 'edukasi.index', 'toko.hasil'] as $rute) {
            $respons->assertSee('href="'.route($rute).'"', false);
        }
    }

    public function test_menu_toko_tersembunyi_saat_modul_mati(): void
    {
        config(['modul.toko.aktif' => false]);

        $this->get('/')->assertSee('Segera hadir')->assertDontSee('/toko"', false);
    }
}
