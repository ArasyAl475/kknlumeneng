<?php

namespace App\Services\Ews;

use App\Models\Ews\Pengaturan;

/**
 * Nomor WhatsApp tujuan laporan warga dari aplikasi HP. Disimpan di web, bukan di kode aplikasi,
 * supaya nomor bisa diganti admin tanpa merilis ulang aplikasi.
 */
class NomorPelaporan
{
    public const KUNCI = 'pelaporan_nomor_wa';

    // Nomor HP Indonesia: 08 diikuti 8 sampai 11 angka, jadi 628 diikuti 8 sampai 11 angka.
    public const POLA = '/^628\d{8,11}$/';

    /**
     * Ubah tulisan bebas admin (0812-3456-7890, +62 812..., 62812...) menjadi format 628xxx.
     * WhatsApp (wa.me) hanya menerima format internasional tanpa tanda plus, spasi, atau strip.
     * Mengembalikan null bila hasilnya bukan nomor HP Indonesia yang masuk akal.
     */
    public static function rapikan(?string $masukan): ?string
    {
        $angka = preg_replace('/\D/', '', (string) $masukan);

        $angka = match (true) {
            str_starts_with($angka, '62') => $angka,
            str_starts_with($angka, '0') => '62'.substr($angka, 1),
            str_starts_with($angka, '8') => '62'.$angka,
            default => $angka,
        };

        return preg_match(self::POLA, $angka) ? $angka : null;
    }

    /** 6281234567890 menjadi 0812-3456-7890 agar mudah dibaca warga. */
    public static function tampil(string $nomor): string
    {
        $lokal = '0'.substr($nomor, 2);

        return implode('-', array_filter([substr($lokal, 0, 4), substr($lokal, 4, 4), substr($lokal, 8)]));
    }

    public static function ambil(): ?string
    {
        return Pengaturan::query()->find(self::KUNCI)?->nilai;
    }

    public static function simpan(string $nomor): void
    {
        Pengaturan::query()->updateOrCreate(['kunci' => self::KUNCI], ['nilai' => $nomor]);
    }

    /**
     * Isi API untuk aplikasi HP. Bentuknya tetap sama walau nomor belum diisi,
     * agar aplikasi cukup memeriksa "tersedia" tanpa menangani galat.
     *
     * @return array{tersedia: bool, nomor: ?string, nomor_tampil: ?string, tautan_wa: ?string}
     */
    public static function data(): array
    {
        $nomor = self::ambil();

        return [
            'tersedia' => $nomor !== null,
            'nomor' => $nomor,
            'nomor_tampil' => $nomor ? self::tampil($nomor) : null,
            'tautan_wa' => $nomor ? "https://wa.me/{$nomor}" : null,
        ];
    }
}
