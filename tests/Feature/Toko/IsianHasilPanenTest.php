<?php

namespace Tests\Feature\Toko;

use App\Services\Toko\IsianHasilPanen;
use Tests\TestCase;

/**
 * Isian yang diketik di Google Sheets harus dibaca sama seperti orang Indonesia menulisnya,
 * dan tidak boleh lebih longgar dari form admin.
 */
class IsianHasilPanenTest extends TestCase
{
    private function periksa(string $jenis, array $mentah): array
    {
        $isian = app(IsianHasilPanen::class);

        return $isian->periksaIsian($isian->definisi($jenis), $mentah);
    }

    private function kopi(array $ubah = []): array
    {
        return $this->periksa('panen-kopi', $ubah + ['tanggal_panen' => '05-10-2026', 'jenis_kopi' => 'Robusta', 'tempat_panen' => 'Krajan', 'jumlah_kg' => 10, 'limbah_kg' => 1]);
    }

    public function test_berbagai_cara_menulis_tanggal_dibaca_benar(): void
    {
        // 46300 = 05-10-2026 sebagai nomor urut hari (sel yang diformat tanggal).
        foreach (['05-10-2026', '05/10/2026', '2026-10-05', 46300] as $tulisan) {
            [$data, $galat] = $this->kopi(['tanggal_panen' => $tulisan]);
            $this->assertSame([], $galat, "Tanggal {$tulisan} seharusnya diterima.");
            $this->assertSame('2026-10-05', $data['tanggal_panen']);
        }
    }

    public function test_tanggal_ngawur_dan_masa_depan_ditolak_seperti_di_form(): void
    {
        foreach (['bukan tanggal', '31-02-2026', now()->addDays(3)->format('d-m-Y')] as $tulisan) {
            [$data, $galat] = $this->kopi(['tanggal_panen' => $tulisan]);
            $this->assertNull($data);
            $this->assertArrayHasKey('tanggal_panen', $galat, "Tanggal {$tulisan} seharusnya ditolak.");
        }
    }

    public function test_desimal_memakai_koma_dan_titik_ribuan(): void
    {
        $this->assertSame('100.5', $this->kopi(['jumlah_kg' => '100,5'])[0]['jumlah_kg']);
        $this->assertSame('1234.5', $this->kopi(['jumlah_kg' => '1.234,5'])[0]['jumlah_kg']);
        $this->assertSame('20.25', $this->kopi(['jumlah_kg' => 20.25])[0]['jumlah_kg']);
    }

    public function test_angka_salah_dan_lebih_dari_dua_desimal_ditolak(): void
    {
        $this->assertArrayHasKey('jumlah_kg', $this->kopi(['jumlah_kg' => 'abc'])[1]);
        $this->assertArrayHasKey('jumlah_kg', $this->kopi(['jumlah_kg' => '10,123'])[1]);
    }

    public function test_teks_dirapikan_dan_kolom_opsional_boleh_kosong(): void
    {
        [$data, $galat] = $this->kopi(['jenis_kopi' => ' robusta ', 'tempat_panen' => 'Kebun  Krajan', 'nama_petani' => '']);

        $this->assertSame([], $galat);
        $this->assertSame('Robusta', $data['jenis_kopi']);
        $this->assertSame('Kebun Krajan', $data['tempat_panen']);
        $this->assertNull($data['nama_petani']);
    }

    public function test_media_tanam_dan_rupiah_dibaca_dari_tulisan_biasa(): void
    {
        [$data, $galat] = $this->periksa('panen-jamur', ['tanggal_panen' => '05-10-2026', 'dengan_cangkang_kopi' => 'tanpa CANGKANG kopi', 'jumlah_baglog' => '40', 'jumlah_kg' => '12,5', 'biaya_baglog_rupiah' => 'Rp 300.000']);

        $this->assertSame([], $galat);
        $this->assertEquals(0, $data['dengan_cangkang_kopi']);
        $this->assertEquals(300000, $data['biaya_baglog_rupiah']);
    }

    public function test_media_tanam_yang_tidak_dikenal_diberi_pesan_jelas(): void
    {
        [$data, $galat] = $this->periksa('penjualan-jamur', ['tanggal_penjualan' => '06-10-2026', 'dengan_cangkang_kopi' => 'Campur', 'jumlah_kg' => 8, 'total_rupiah' => 240000]);

        $this->assertNull($data);
        $this->assertSame('Media tanam harus ditulis "Dengan cangkang kopi" atau "Tanpa cangkang kopi".', $galat['dengan_cangkang_kopi']);
    }
}
