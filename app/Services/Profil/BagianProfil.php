<?php

namespace App\Services\Profil;

/**
 * Isian profil desa dibagi menjadi formulir-formulir pendek di admin, agar perangkat desa
 * tidak berhadapan dengan satu formulir panjang yang menakutkan.
 */
final class BagianProfil
{
    public const DAFTAR = [
        'identitas' => [
            'judul' => 'Identitas & sambutan',
            'keterangan' => 'Nama desa, semboyan, dan sambutan kepala desa yang tampil paling atas di halaman Profil Desa.',
            // Foto sampul dan foto kepala desa kini diatur di menu Desain Web (slot profil.hero dan profil.kepala-desa).
            'foto' => [],
        ],
        'sejarah' => [
            'judul' => 'Sejarah, visi & misi',
            'keterangan' => 'Cerita asal-usul desa serta visi dan misi pemerintah desa.',
            'foto' => [],
        ],
        'wilayah' => [
            'judul' => 'Data wilayah',
            'keterangan' => 'Angka penduduk, luas, dan batas wilayah. Isian yang dikosongkan tidak tampil di situs.',
            'foto' => [],
        ],
        'kontak' => [
            'judul' => 'Kontak kantor desa',
            'keterangan' => 'Alamat, nomor telepon, dan jam pelayanan kantor desa.',
            'foto' => [],
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function kunci(): array
    {
        return array_keys(self::DAFTAR);
    }

    /**
     * @return array{judul: string, keterangan: string, foto: array<int, string>}
     */
    public static function ambil(string $bagian): array
    {
        return self::DAFTAR[$bagian];
    }
}
