<?php

namespace Tests\Unit\Ews;

use App\Services\Ews\NomorPelaporan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NomorPelaporanTest extends TestCase
{
    #[DataProvider('tulisanSah')]
    public function test_berbagai_gaya_tulisan_dirapikan(string $masukan, string $hasil): void
    {
        $this->assertSame($hasil, NomorPelaporan::rapikan($masukan));
    }

    public static function tulisanSah(): array
    {
        return [
            'awalan 0' => ['081234567890', '6281234567890'],
            'dengan strip' => ['0812-3456-7890', '6281234567890'],
            'dengan spasi' => ['0812 3456 7890', '6281234567890'],
            'awalan +62' => ['+62 812-3456-7890', '6281234567890'],
            'awalan 62' => ['6281234567890', '6281234567890'],
            'tanpa 0' => ['81234567890', '6281234567890'],
            'terpendek' => ['0812345678', '62812345678'],
            'terpanjang' => ['0812345678901', '62812345678901'],
        ];
    }

    #[DataProvider('tulisanSalah')]
    public function test_nomor_tidak_masuk_akal_ditolak(?string $masukan): void
    {
        $this->assertNull(NomorPelaporan::rapikan($masukan));
    }

    public static function tulisanSalah(): array
    {
        return [
            'kosong' => [''],
            'null' => [null],
            'huruf' => ['nomor saya'],
            'telepon rumah' => ['0285123456'],
            'terlalu pendek' => ['08123'],
            'terlalu panjang' => ['08123456789012'],
            'negara lain' => ['+60123456789'],
        ];
    }

    public function test_tampilan_mudah_dibaca(): void
    {
        $this->assertSame('0812-3456-7890', NomorPelaporan::tampil('6281234567890'));
        $this->assertSame('0812-3456-78', NomorPelaporan::tampil('62812345678'));
    }
}
