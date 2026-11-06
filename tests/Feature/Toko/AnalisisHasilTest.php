<?php

namespace Tests\Feature\Toko;

use App\Models\Toko\PanenJamur;
use App\Models\Toko\PanenKopi;
use App\Models\Toko\PenjualanJamur;
use App\Services\Toko\AnalisisHasil;
use App\Services\Toko\RekapHasil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnalisisHasilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-06 10:00:00');
    }

    private function kopi(string $tanggal, float $kg, string $jenis = 'Robusta'): void
    {
        PanenKopi::query()->create(['tanggal_panen' => $tanggal, 'jenis_kopi' => $jenis, 'tempat_panen' => 'Krajan', 'jumlah_kg' => $kg, 'limbah_kg' => $kg / 4]);
    }

    private function analisis(): AnalisisHasil
    {
        return app(AnalisisHasil::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function perubahan(array $filter): ?array
    {
        $rekap = app(RekapHasil::class);

        return $this->analisis()->perubahan($filter, $rekap->kopi($filter), $rekap->jamur($filter), $rekap->penjualan($filter));
    }

    public function test_deret_kosong_bila_belum_ada_catatan(): void
    {
        $this->assertSame(['ada' => false, 'skala' => []], $this->analisis()->deret());
    }

    public function test_deret_menjumlah_per_hari_bulan_dan_tahun_dengan_label_indonesia(): void
    {
        $this->kopi('2026-08-03', 100);
        $this->kopi('2026-08-03', 20, 'Arabika');
        $this->kopi('2026-10-05', 50);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-05', 'jumlah_kg' => 2.5, 'total_rupiah' => 100000]);

        $skala = $this->analisis()->deret()['skala'];

        $this->assertSame(['Agu', 'Sep', 'Okt'], $skala['bulan']['label']);
        $this->assertSame(['Agustus 2026', 'September 2026', 'Oktober 2026'], $skala['bulan']['lengkap']);
        $this->assertSame([120.0, 0.0, 50.0], $skala['bulan']['kopi']);
        $this->assertSame([0, 0, 100000], $skala['bulan']['pendapatan']);
        $this->assertSame(['2026'], $skala['tahun']['label']);
        $this->assertSame([170.0], $skala['tahun']['kopi']);
        // Hari tanpa catatan bernilai 0 agar sumbu waktu jujur.
        $this->assertSame('3 Agu', $skala['hari']['label'][0]);
        $this->assertSame('5 Okt', end($skala['hari']['label']));
        $this->assertSame(120.0, $skala['hari']['kopi'][0]);
        $this->assertSame(0.0, $skala['hari']['kopi'][1]);
    }

    public function test_deret_hari_dibatasi_90_hari_terakhir(): void
    {
        $this->kopi('2026-01-10', 10);
        $this->kopi('2026-10-05', 20);

        $hari = $this->analisis()->deret()['skala']['hari'];

        $this->assertCount(AnalisisHasil::BATAS_HARI, $hari['label']);
        $this->assertTrue($hari['terpotong']);
        $this->assertSame('Senin, 05-10-2026', end($hari['lengkap']));
    }

    public function test_deret_mengikuti_filter_dan_berhenti_di_hari_ini(): void
    {
        $this->kopi('2026-10-05', 10);
        $this->kopi('2026-10-06', 99, 'Arabika');

        $skala = $this->analisis()->deret(['dari' => '2026-01-01', 'sampai' => '2026-12-31', 'jenis' => 'Robusta'])['skala'];

        // Januari sampai November (hari ini 6 November), bukan sampai Desember.
        $this->assertCount(11, $skala['bulan']['label']);
        $this->assertSame('Nov', end($skala['bulan']['label']));
        $this->assertSame(10.0, $skala['tahun']['kopi'][0]);
    }

    public function test_deret_menyebut_tahun_di_sumbu_bila_melewati_pergantian_tahun(): void
    {
        $this->kopi('2025-12-20', 10);
        $this->kopi('2026-02-02', 10);

        $this->assertSame(['Des 25', 'Jan 26', 'Feb 26'], $this->analisis()->deret()['skala']['bulan']['label']);
    }

    public function test_perubahan_bulan_ini_dibanding_tanggal_yang_sama_bulan_lalu(): void
    {
        // Hari ini 6 November: bulan ini baru berjalan 6 hari, jadi pembandingnya 1-6 Oktober.
        $this->kopi('2026-10-02', 100);
        $this->kopi('2026-10-20', 900);
        $this->kopi('2026-11-03', 150);

        $perubahan = $this->perubahan(['dari' => '2026-11-01', 'sampai' => '2026-11-30']);

        $this->assertSame('bulan lalu', $perubahan['label']);
        $this->assertSame(['persen' => 50, 'arah' => 'naik'], $perubahan['kopi']);
        $this->assertSame('Dibandingkan dengan 01-10-2026 sampai 06-10-2026.', $perubahan['keterangan']);
        $this->assertNull($perubahan['pendapatan']);
    }

    public function test_perubahan_turun_dan_tahun_lalu(): void
    {
        $this->kopi('2025-06-01', 200);
        $this->kopi('2026-06-01', 150);

        $perubahan = $this->perubahan(['dari' => '2026-01-01', 'sampai' => '2026-12-31']);

        $this->assertSame('tahun lalu', $perubahan['label']);
        $this->assertSame(['persen' => 25, 'arah' => 'turun'], $perubahan['kopi']);
    }

    public function test_perubahan_dengan_rentang_sendiri_memakai_periode_sama_panjang(): void
    {
        $this->kopi('2026-09-12', 100);
        $this->kopi('2026-09-25', 100);
        $this->kopi('2026-10-12', 100);

        // 10-20 Oktober (11 hari) dibandingkan dengan 29 September-9 Oktober.
        $perubahan = $this->perubahan(['dari' => '2026-10-10', 'sampai' => '2026-10-20']);
        $this->assertNull($perubahan);

        $this->kopi('2026-10-01', 50);
        $perubahan = $this->perubahan(['dari' => '2026-10-10', 'sampai' => '2026-10-20']);
        $this->assertSame('periode sebelumnya', $perubahan['label']);
        $this->assertSame(['persen' => 100, 'arah' => 'naik'], $perubahan['kopi']);
        $this->assertSame('Dibandingkan dengan 29-09-2026 sampai 09-10-2026.', $perubahan['keterangan']);
    }

    public function test_perubahan_tidak_ada_untuk_semua_waktu_atau_tanpa_pembanding(): void
    {
        $this->kopi('2026-10-02', 100);

        $this->assertNull($this->perubahan([]));
        $this->assertNull($this->perubahan(['dari' => '2026-01-01', 'sampai' => '2026-12-31']));
    }

    public function test_halaman_menampilkan_perubahan_dan_grafik(): void
    {
        $this->kopi('2026-10-02', 100);
        $this->kopi('2026-11-03', 150);

        $this->get('/toko/hasil?dari=2026-11-01&sampai=2026-11-30')->assertOk()
            ->assertSee('Naik 50%')
            ->assertSee('dari bulan lalu')
            ->assertSee('Dibandingkan dengan 01-10-2026 sampai 06-10-2026.')
            ->assertSee('Perkembangan Hasil Desa')
            ->assertSee('data-grafik', false)
            ->assertSee('Lihat sebagai tabel')
            ->assertDontSee('Belum ada data untuk periode ini');
    }

    public function test_chip_filter_aktif_bisa_dihapus_satu_per_satu(): void
    {
        $this->kopi('2026-10-05', 10);

        $respons = $this->get('/toko/hasil?jenis=Robusta&tempat=Krajan&dari=2026-10-01&sampai=2026-10-20');

        $respons->assertOk()
            ->assertSee('Jenis: Robusta')
            ->assertSee('Tempat: Krajan')
            ->assertSee('Tanggal: 01-10-2026 sampai 20-10-2026')
            ->assertSee('Hapus pilihan Jenis: Robusta');
        // Menghapus chip jenis tidak menghapus pilihan lain.
        $respons->assertSee(e(route('toko.hasil', ['dari' => '2026-10-01', 'sampai' => '2026-10-20', 'tempat' => 'Krajan'])), false);
    }

    public function test_pintasan_waktu_tidak_dobel_sebagai_chip(): void
    {
        $this->get('/toko/hasil?dari=2026-11-01&sampai=2026-11-30')->assertOk()
            ->assertDontSee('Tanggal:')
            ->assertDontSee('chip-daftar', false);
        $this->get('/toko/hasil')->assertOk()->assertDontSee('chip-daftar', false);
    }

    public function test_keadaan_kosong_dengan_filter_menawarkan_semua_waktu(): void
    {
        $this->kopi('2025-01-05', 10);

        $this->get('/toko/hasil?dari=2026-11-01&sampai=2026-11-30&jenis=Robusta')->assertOk()
            ->assertSee('Belum ada data untuk periode ini')
            ->assertSee('Lihat semua waktu')
            ->assertSee(e(route('toko.hasil', ['jenis' => 'Robusta'])), false);
    }

    public function test_kesimpulan_baglog_lebih_banyak_lebih_sedikit_dan_belum_cukup(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 50, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => true]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 40, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => false]);
        $rekap = app(RekapHasil::class);

        $lebih = $this->analisis()->kesimpulanBaglog($rekap->perbandinganJamur());

        $this->assertTrue($lebih['cukup']);
        $this->assertSame('25% lebih banyak', $lebih['selisih']);
        $this->assertSame(100.0, $lebih['dengan']['lebar']);
        $this->assertSame(80.0, $lebih['tanpa']['lebar']);

        PanenJamur::query()->where('dengan_cangkang_kopi', true)->update(['jumlah_kg' => 30]);
        $kurang = $this->analisis()->kesimpulanBaglog($rekap->perbandinganJamur());
        $this->assertSame('25% lebih sedikit', $kurang['selisih']);

        PanenJamur::query()->where('dengan_cangkang_kopi', false)->delete();
        $belum = $this->analisis()->kesimpulanBaglog($rekap->perbandinganJamur());
        $this->assertFalse($belum['cukup']);
        $this->assertSame('Data belum cukup untuk dibandingkan.', $belum['kalimat']);
    }

    public function test_catatan_penjualan_lama_tanpa_media_dijelaskan(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 30, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => true]);
        PenjualanJamur::query()->create(['tanggal_penjualan' => '2026-10-08', 'jumlah_kg' => 2, 'total_rupiah' => 90000]);

        $this->get('/toko/hasil')->assertOk()->assertSee('Sebagian penjualan lama belum mencatat media tanam');
    }

    public function test_baglog_per_bulan_dibagi_per_media_dan_bulan_kosong_null(): void
    {
        PanenJamur::query()->create(['tanggal_panen' => '2026-08-05', 'jumlah_kg' => 50, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => true]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-08-12', 'jumlah_kg' => 30, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => true]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-08-05', 'jumlah_kg' => 40, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => false]);
        PanenJamur::query()->create(['tanggal_panen' => '2026-10-02', 'jumlah_kg' => 45, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => false]);
        // Tanpa jumlah baglog: tidak ikut dihitung.
        PanenJamur::query()->create(['tanggal_panen' => '2026-09-02', 'jumlah_kg' => 99, 'dengan_cangkang_kopi' => true]);

        $data = $this->analisis()->baglogPerBulan();

        $this->assertTrue($data['ada']);
        $this->assertSame(['Agu', 'Sep', 'Okt'], $data['label']);
        $this->assertSame([0.4, null, null], $data['dengan']);
        $this->assertSame([0.4, null, 0.45], $data['tanpa']);
        $this->assertFalse($data['terpotong']);
    }

    public function test_baglog_per_bulan_kosong_dan_halaman_menampilkan_grafiknya(): void
    {
        $this->assertSame(['ada' => false], $this->analisis()->baglogPerBulan());
        $this->get('/toko/hasil')->assertOk()->assertDontSee('data-grafik-baglog', false);

        PanenJamur::query()->create(['tanggal_panen' => '2026-10-05', 'jumlah_kg' => 30, 'jumlah_baglog' => 100, 'dengan_cangkang_kopi' => true]);

        $this->get('/toko/hasil')->assertOk()
            ->assertSee('data-grafik-baglog', false)
            ->assertSee('Hasil per baglog tiap bulan')
            ->assertSee('Belum ada catatan');
    }
}
