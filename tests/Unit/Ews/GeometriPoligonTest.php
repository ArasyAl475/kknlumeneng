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

    public function test_bangun_sah_termasuk_cekung(): void
    {
        $this->assertNull(GeometriPoligon::masalah([[-7.1, 109.1], [-7.1, 109.2], [-7.2, 109.2]]));
        $this->assertNull(GeometriPoligon::masalah([[-7.1, 109.1], [-7.1, 109.3], [-7.2, 109.2], [-7.3, 109.3], [-7.3, 109.1]]));
    }
}
