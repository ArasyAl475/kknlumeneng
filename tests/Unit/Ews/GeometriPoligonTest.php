<?php

namespace Tests\Unit\Ews;

use App\Services\Ews\GeometriPoligon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GeometriPoligonTest extends TestCase
{
    #[DataProvider('masukanRusak')]
    public function test_masukan_rusak_ditolak(mixed $masukan): void
    {
        $this->assertNull(GeometriPoligon::normalkan($masukan));
    }

    public static function masukanRusak(): array
    {
        return [
            'bukan daftar' => ['teks'],
            'objek' => [['a' => [1, 2]]],
            'titik satu nilai' => [[[1], [2], [3]]],
            'bukan angka' => [[['x', 'y'], [1, 2], [3, 4]]],
            'lintang lewat 90' => [[[91, 0], [1, 2], [3, 4]]],
            'bujur lewat 180' => [[[0, 181], [1, 2], [3, 4]]],
        ];
    }

    public function test_titik_penutup_dibuang_dan_dibulatkan(): void
    {
        $this->assertSame(
            [[-7.1, 109.123457], [-7.1, 109.2], [-7.2, 109.2]],
            GeometriPoligon::normalkan([[-7.1, 109.1234567], [-7.1, 109.2], [-7.2, 109.2], [-7.1, 109.1234567]]),
        );
    }

    #[DataProvider('bentukSalah')]
    public function test_bentuk_salah_punya_pesan(array $titik, string $pesan): void
    {
        $this->assertStringContainsString($pesan, (string) GeometriPoligon::masalah($titik));
    }

    public static function bentukSalah(): array
    {
        return [
            'dua titik' => [[[-7.1, 109.1], [-7.2, 109.2]], 'minimal 3 titik'],
            'titik kembar' => [[[-7.1, 109.1], [-7.1, 109.1], [-7.2, 109.2]], 'minimal 3 titik'],
            'segaris' => [[[-7.1, 109.1], [-7.2, 109.2], [-7.3, 109.3]], 'garis'],
            'menyilang' => [[[-7.1, 109.1], [-7.2, 109.2], [-7.1, 109.2], [-7.2, 109.1]], 'bersilangan'],
            'terlalu banyak' => [array_fill(0, 201, [-7.1, 109.1]), 'Terlalu banyak'],
        ];
    }

    public function test_teks_satu_titik_per_baris_dibaca_seperti_json(): void
    {
        $dariBaris = GeometriPoligon::dariTeks("-7.185772, 109.588701\r\n\r\n-7.185060, 109.587750\n-7.185356, 109.587502\n");

        $this->assertSame([[-7.185772, 109.588701], [-7.18506, 109.58775], [-7.185356, 109.587502]], $dariBaris);
        $this->assertSame($dariBaris, GeometriPoligon::dariTeks('[[-7.185772,109.588701],[-7.18506,109.58775],[-7.185356,109.587502]]'));
    }

    public function test_spasi_nyasar_di_dalam_angka_diabaikan(): void
    {
        $this->assertSame([[-7.186625, 109.591824]], GeometriPoligon::dariTeks('-7. 186625, 109.591824'));
    }

    #[DataProvider('teksRusak')]
    public function test_teks_rusak_ditolak(string $teks): void
    {
        $this->assertNull(GeometriPoligon::dariTeks($teks));
    }

    public static function teksRusak(): array
    {
        return [
            'kosong' => [''],
            'satu nilai di satu baris' => ["-7.1\n-7.2"],
            'tiga nilai di satu baris' => ['-7.1, 109.1, 5'],
            'bukan angka' => ['Titik lokasi EWS, abc'],
            'json rusak' => ['[[1,2'],
            'lintang lewat 90' => ['-97.1, 109.1'],
        ];
    }

    public function test_bangun_sah_termasuk_cekung(): void
    {
        $this->assertNull(GeometriPoligon::masalah([[-7.1, 109.1], [-7.1, 109.2], [-7.2, 109.2]]));
        $this->assertNull(GeometriPoligon::masalah([[-7.1, 109.1], [-7.1, 109.3], [-7.2, 109.2], [-7.3, 109.3], [-7.3, 109.1]]));
    }
}
