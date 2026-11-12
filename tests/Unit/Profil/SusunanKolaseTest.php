<?php

namespace Tests\Unit\Profil;

use App\Services\Profil\SusunanKolase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SusunanKolaseTest extends TestCase
{
    #[DataProvider('jumlahFoto')]
    public function test_setiap_susunan_menutup_semua_sel_tepat_satu_kali(int $jumlah): void
    {
        $sel = [];

        foreach (SusunanKolase::area($jumlah) as $area) {
            array_push($sel, ...SusunanKolase::sel($area));
        }

        // Tidak ada sel kosong (celah) dan tidak ada sel yang dipakai dua foto (tumpang tindih).
        $this->assertCount(SusunanKolase::jumlahSel(), $sel);
        $this->assertCount(SusunanKolase::jumlahSel(), array_unique($sel));
    }

    public static function jumlahFoto(): array
    {
        return [[1], [2], [3], [4], [5], [6]];
    }

    public function test_jumlah_area_mengikuti_jumlah_foto_dan_dibatasi(): void
    {
        $this->assertCount(3, SusunanKolase::area(3));
        $this->assertCount(SusunanKolase::MAKS_TAMPIL, SusunanKolase::area(20));
        $this->assertCount(1, SusunanKolase::area(0));
    }

    public function test_foto_pertama_adalah_yang_terbesar(): void
    {
        foreach (range(2, SusunanKolase::MAKS_TAMPIL) as $jumlah) {
            $luas = array_map(fn (string $a) => count(SusunanKolase::sel($a)), SusunanKolase::area($jumlah));

            $this->assertSame(max($luas), $luas[0], "Susunan {$jumlah} foto");
        }
    }
}
