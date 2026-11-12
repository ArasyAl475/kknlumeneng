<?php

namespace App\Services\Ews;

/**
 * Isi tetap dari Kajian Pemetaan Bencana Desa Lumeneng 2024 (LPBI NU Kab. Pekalongan dan
 * Ringan Tangan Indonesia): gambar peta kerawanan dan gambar bentang alam desa.
 * Ini gambaran kerawanan jangka panjang, BUKAN status terkini; status terkini dihitung dari sensor.
 * Peta diolah dari dokumen kajian (resolusinya terbatas), jadi disajikan sebagai gambar biasa.
 */
final class PetaKerawanan
{
    public const SUMBER = 'Kajian Pemetaan Bencana Desa Lumeneng 2024 (LPBI NU Kab. Pekalongan dan Ringan Tangan Indonesia)';

    /**
     * @return array<int, array{berkas: string, judul: string, penjelasan: string, alt: string}>
     */
    public static function peta(): array
    {
        return [
            [
                'berkas' => 'rawan-longsor',
                'judul' => 'Peta rawan longsor',
                'penjelasan' => 'Hasil tumpang susun tutupan lahan, curah hujan, kemiringan lereng, dan zona kerentanan gerakan tanah.',
                'alt' => 'Peta rawan longsor Desa Lumeneng dengan daerah rawan berwarna merah, kuning, dan biru. Legenda ada di sisi kanan peta.',
            ],
            [
                'berkas' => 'gerakan-tanah',
                'judul' => 'Zona kerentanan gerakan tanah',
                'penjelasan' => 'Sebaran zona kerentanan gerakan tanah menurut data Dinas ESDM Jawa Tengah.',
                'alt' => 'Peta zona kerentanan gerakan tanah Desa Lumeneng. Zona tinggi berwarna merah, menengah kuning.',
            ],
            [
                'berkas' => 'kemiringan-lereng',
                'judul' => 'Kemiringan lereng',
                'penjelasan' => 'Kelas lereng dari datar sampai sangat curam, dihitung dari data elevasi digital (DEM) BIG.',
                'alt' => 'Peta kemiringan lereng Desa Lumeneng. Warna merah tua menandakan lereng sangat curam.',
            ],
            [
                'berkas' => 'tutupan-lahan',
                'judul' => 'Tutupan lahan',
                'penjelasan' => 'Sebaran vegetasi dan permukiman hasil tafsir citra satelit. Lebih dari separuh desa berupa hutan lindung.',
                'alt' => 'Peta tutupan lahan Desa Lumeneng. Hijau tua menandakan vegetasi sangat tertutup.',
            ],
            [
                'berkas' => 'curah-hujan',
                'judul' => 'Curah hujan tahunan',
                'penjelasan' => 'Curah hujan tahunan di sekitar desa tergolong tinggi, ribuan milimeter per tahun. Ini salah satu penyebab tanah mudah jenuh air.',
                'alt' => 'Peta curah hujan tahunan di sekitar Desa Lumeneng dengan garis-garis kisaran milimeter per tahun.',
            ],
        ];
    }

    /**
     * Teks bagian peta 3D di beranda. Gambarnya sendiri diatur admin di menu Desain Web (slot beranda.peta-3d).
     *
     * @return array{judul: string, penjelasan: string}
     */
    public static function bentangAlam(): array
    {
        return [
            'judul' => 'Bentang alam Desa Lumeneng',
            'penjelasan' => 'Desa berbukit dengan enam dusun tersebar di lerengnya. Putar dan perbesar untuk mengenali letak dusun dan dukuh.',
        ];
    }
}
