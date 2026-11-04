<?php

namespace Tests\Unit\Fondasi;

use Database\Seeders\AdminSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SandiAdminTest extends TestCase
{
    #[DataProvider('sandi')]
    public function test_menilai_kekuatan_sandi(string $sandi, bool $kuat): void
    {
        $this->assertSame($kuat, AdminSeeder::sandiKuat($sandi));
    }

    public static function sandi(): array
    {
        return [
            'pendek' => ['abc123', false],
            'tanpa angka' => ['hanyahurufsaja', false],
            'tanpa huruf' => ['987654321098', false],
            'kata umum' => ['password12345678', false],
            'nama desa' => ['Lumeneng2026abc', false],
            'kuat' => ['tebing-Hijau-7-rimba', true],
        ];
    }
}
