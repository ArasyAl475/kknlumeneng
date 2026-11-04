<?php

namespace Tests\Unit\Ews;

use App\Services\Ews\PenilaianStatus;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PenilaianStatusTest extends TestCase
{
    private const AMBANG = [
        'kelembapan_tanah' => ['waspada' => 50, 'validasi' => 80],
        'curah_hujan' => ['waspada' => 10, 'validasi' => 20],
        'kemiringan' => ['waspada' => 5, 'validasi' => 15],
    ];

    // Nilai contoh per sensor per level.
    private const CONTOH = [
        'kelembapan_tanah' => [0 => 30.0, 1 => 60.0, 2 => 90.0],
        'curah_hujan' => [0 => 2.0, 1 => 12.0, 2 => 25.0],
        'kemiringan' => [0 => 1.0, 1 => 7.0, 2 => 20.0],
    ];

    #[DataProvider('batasTepat')]
    public function test_level_pada_batas_tepat(?float $nilai, ?int $harapan): void
    {
        $this->assertSame($harapan, PenilaianStatus::level($nilai, ['waspada' => 50, 'validasi' => 80]));
    }

    public static function batasTepat(): array
    {
        return [
            'null bukan nol' => [null, null],
            'nol' => [0.0, 0],
            'tepat di bawah waspada' => [49.99, 0],
            'tepat waspada' => [50.0, 1],
            'tepat di bawah validasi' => [79.99, 1],
            'tepat validasi' => [80.0, 2],
            'di atas validasi' => [100.0, 2],
        ];
    }

    #[DataProvider('semuaKombinasiLevel')]
    public function test_semua_kombinasi_level(?int $tanah, ?int $hujan, ?int $miring): void
    {
        $bacaan = [
            'kelembapan_tanah' => $tanah === null ? null : self::CONTOH['kelembapan_tanah'][$tanah],
            'curah_hujan' => $hujan === null ? null : self::CONTOH['curah_hujan'][$hujan],
            'kemiringan' => $miring === null ? null : self::CONTOH['kemiringan'][$miring],
        ];

        $hasil = PenilaianStatus::nilai($bacaan, self::AMBANG);
        $tertinggi = max($tanah ?? 0, $hujan ?? 0, $miring ?? 0);

        $this->assertSame($tertinggi === 0 ? 'Aman' : 'Waspada', $hasil['status']);
        $this->assertSame($tertinggi === 0 ? 'hijau' : 'kuning', $hasil['warna']);
        $this->assertSame($tertinggi === 2, $hasil['perlu_validasi']);
        $this->assertSame(['kelembapan_tanah' => $tanah, 'curah_hujan' => $hujan, 'kemiringan' => $miring], $hasil['level']);

        // Angka sensor saja tidak pernah menghasilkan Awas.
        $this->assertNotSame('Awas', $hasil['status']);
    }

    public static function semuaKombinasiLevel(): iterable
    {
        foreach ([null, 0, 1, 2] as $tanah) {
            foreach ([null, 0, 1, 2] as $hujan) {
                foreach ([null, 0, 1, 2] as $miring) {
                    yield 'tanah='.($tanah ?? 'null').' hujan='.($hujan ?? 'null').' miring='.($miring ?? 'null') => [$tanah, $hujan, $miring];
                }
            }
        }
    }

    public function test_semua_sensor_null_dianggap_aman_bukan_nol(): void
    {
        $hasil = PenilaianStatus::nilai([], self::AMBANG);

        $this->assertSame('Aman', $hasil['status']);
        $this->assertNull($hasil['pemicu']);
        $this->assertSame(['kelembapan_tanah' => null, 'curah_hujan' => null, 'kemiringan' => null], $hasil['level']);
    }

    public function test_kabel_putus_selalu_awas_tanpa_validasi(): void
    {
        $hasil = PenilaianStatus::nilai(['kelembapan_tanah' => 90.0], self::AMBANG, kabelPutus: true);

        $this->assertSame('Awas', $hasil['status']);
        $this->assertSame('merah', $hasil['warna']);
        $this->assertFalse($hasil['perlu_validasi']);
        $this->assertSame('Kabel EWS putus, Kelembapan tanah 90%', $hasil['pemicu']);
    }

    public function test_kabel_putus_tanpa_sensor_tetap_awas(): void
    {
        $hasil = PenilaianStatus::nilai([], self::AMBANG, kabelPutus: true);

        $this->assertSame('Awas', $hasil['status']);
        $this->assertSame('Kabel EWS putus', $hasil['pemicu']);
    }

    #[DataProvider('kemiringanNegatif')]
    public function test_kemiringan_dinilai_mutlak(float $nilai, int $level): void
    {
        $this->assertSame($level, PenilaianStatus::levelSensor(['kemiringan' => $nilai], self::AMBANG)['kemiringan']);
    }

    public static function kemiringanNegatif(): array
    {
        return [
            'negatif normal' => [-4.99, 0],
            'negatif tepat waspada' => [-5.0, 1],
            'negatif tepat validasi' => [-15.0, 2],
            'positif tepat validasi' => [15.0, 2],
        ];
    }

    public function test_pemicu_kemiringan_negatif_tampil_mutlak_dengan_koma(): void
    {
        $hasil = PenilaianStatus::nilai(['kemiringan' => -7.5], self::AMBANG);

        $this->assertSame('Kemiringan 7,5°', $hasil['pemicu']);
    }

    public function test_kelembapan_udara_tidak_menentukan_status(): void
    {
        $hasil = PenilaianStatus::nilai(['kelembapan_udara' => 100.0], self::AMBANG);

        $this->assertSame('Aman', $hasil['status']);
        $this->assertArrayNotHasKey('kelembapan_udara', $hasil['level']);
    }

    public function test_ambang_yang_diubah_ikut_berlaku(): void
    {
        $ambang = ['kemiringan' => ['waspada' => 2, 'validasi' => 4]] + self::AMBANG;

        $this->assertTrue(PenilaianStatus::nilai(['kemiringan' => 4.0], $ambang)['perlu_validasi']);
    }

    public function test_status_awas_ditahan_walau_data_normal(): void
    {
        $this->assertSame(['status' => 'Awas', 'warna' => 'merah'], PenilaianStatus::statusWilayah('Aman', true));
        $this->assertSame(['status' => 'Aman', 'warna' => 'hijau'], PenilaianStatus::statusWilayah('Aman', false));
        $this->assertSame(['status' => 'Awas', 'warna' => 'merah'], PenilaianStatus::statusWilayah(null, true));
        $this->assertSame(['status' => null, 'warna' => 'kosong'], PenilaianStatus::statusWilayah(null, false));
    }

    public function test_warna_terburuk(): void
    {
        $this->assertSame('kosong', PenilaianStatus::warnaTerburuk([]));
        $this->assertSame('hijau', PenilaianStatus::warnaTerburuk(['kosong', 'hijau']));
        $this->assertSame('kuning', PenilaianStatus::warnaTerburuk(['hijau', 'kuning', 'hijau']));
        $this->assertSame('merah', PenilaianStatus::warnaTerburuk(['merah', 'kuning', 'kosong']));
    }

    public function test_alat_offline_tepat_pada_batas(): void
    {
        $sekarang = CarbonImmutable::parse('2026-11-04 12:00:00');

        $this->assertFalse(PenilaianStatus::alatOffline(null, 15, $sekarang), 'belum pernah mengirim bukan offline');
        $this->assertFalse(PenilaianStatus::alatOffline($sekarang->subMinutes(14), 15, $sekarang));
        $this->assertTrue(PenilaianStatus::alatOffline($sekarang->subMinutes(15), 15, $sekarang));
        $this->assertFalse(PenilaianStatus::alatOffline($sekarang->subDays(2), 0, $sekarang), 'batas 0 berarti mati');
    }

    public function test_format_angka(): void
    {
        $this->assertSame('24', PenilaianStatus::angka(24.0));
        $this->assertSame('12,35', PenilaianStatus::angka(12.35));
        $this->assertSame('0', PenilaianStatus::angka(0));
    }
}
