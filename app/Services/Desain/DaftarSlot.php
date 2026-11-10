<?php

namespace App\Services\Desain;

use App\Models\Desain\SlotGambar;

/**
 * Daftar resmi semua tempat gambar tata letak. Ditulis di kode (bukan hanya di basis data)
 * agar situs tetap punya gambar bawaan walau seeder belum dijalankan, dan agar slot baru
 * cukup ditambah di sini lalu otomatis muncul di menu Desain Web.
 *
 * Alt kosong berarti gambar hanya hiasan: pembaca layar melewatinya.
 */
final class DaftarSlot
{
    public const CADANGAN = 'images/default/cadangan.svg';

    private const HERO_BANNER = '1920 × 640 px (mendatar, 3:1)';

    public const SEMUA = [
        'beranda.hero' => [
            'nama' => 'Foto utama Beranda',
            'halaman' => 'Beranda',
            'penjelasan' => 'Foto besar paling atas di halaman depan, di belakang tulisan "Desa Lumeneng" dan tombol Jelajahi Desa. Pilih pemandangan desa yang lebar, misalnya foto udara kebun kopi atau lereng. Bagian tengah foto sebaiknya tidak terlalu ramai agar tulisan terbaca.',
            'ukuran' => '1920 × 1080 px (mendatar, 16:9)',
            'rasio' => '16/9',
            'alt' => '',
        ],
        'beranda.angka' => [
            'nama' => 'Latar "Desa dalam angka"',
            'halaman' => 'Beranda',
            'penjelasan' => 'Foto latar pita biru tua "Desa dalam angka" di halaman depan, di belakang angka penduduk dan luas wilayah. Foto dibuat redup otomatis agar angka tetap terbaca.',
            'ukuran' => '1920 × 900 px (mendatar)',
            'rasio' => '16/9',
            'alt' => '',
        ],
        'beranda.produk' => [
            'nama' => 'Foto produk warga',
            'halaman' => 'Beranda',
            'penjelasan' => 'Muncul di bagian "Produk warga" di halaman depan, di samping ajakan membuka Toko Desa. Cocok untuk foto kopi, jamur tiram, atau kegiatan panen.',
            'ukuran' => '1200 × 900 px (4:3)',
            'rasio' => '4/3',
            'alt' => 'Produk hasil kebun warga Desa Lumeneng',
        ],
        'beranda.tentang' => [
            'nama' => 'Foto kegiatan program',
            'halaman' => 'Beranda',
            'penjelasan' => 'Muncul di bagian "Tentang program" di halaman depan, di samping penjelasan program KKN dan daftar mitra. Cocok untuk foto kegiatan bersama warga atau kader.',
            'ukuran' => '1200 × 900 px (4:3)',
            'rasio' => '4/3',
            'alt' => 'Kegiatan program bersama warga Desa Lumeneng',
        ],
        'profil.hero' => [
            'nama' => 'Foto sampul Profil Desa',
            'halaman' => 'Profil Desa',
            'penjelasan' => 'Foto lebar paling atas di halaman Profil Desa, Visi & Misi, dan Pemerintahan Desa, di belakang nama desa.',
            'ukuran' => '1920 × 800 px (mendatar, 12:5)',
            'rasio' => '12/5',
            'alt' => '',
        ],
        'profil.kepala-desa' => [
            'nama' => 'Foto kepala desa',
            'halaman' => 'Profil Desa',
            'penjelasan' => 'Foto bulat di bagian "Sambutan" halaman Profil Desa. Pakai foto setengah badan, wajah menghadap depan, latar polos.',
            'ukuran' => '800 × 1000 px (tegak, 4:5)',
            'rasio' => '4/5',
            'alt' => 'Foto kepala desa',
        ],
        'berita.hero' => [
            'nama' => 'Latar judul Berita',
            'halaman' => 'Berita',
            'penjelasan' => 'Gambar latar di bagian judul halaman Berita Desa. Tulisan judul tampil di atasnya dengan lapisan gelap.',
            'ukuran' => self::HERO_BANNER,
            'rasio' => '3/1',
            'alt' => '',
        ],
        'galeri.hero' => [
            'nama' => 'Latar judul Galeri',
            'halaman' => 'Galeri',
            'penjelasan' => 'Gambar latar di bagian judul halaman Galeri Desa. Foto terbaik desa paling cocok di sini.',
            'ukuran' => self::HERO_BANNER,
            'rasio' => '3/1',
            'alt' => '',
        ],
        'ews.hero' => [
            'nama' => 'Latar judul Peringatan Dini',
            'halaman' => 'Peringatan Dini',
            'penjelasan' => 'Gambar latar tipis di bagian judul halaman Peringatan Dini. Sengaja dibuat pendek agar status longsor tetap menjadi perhatian utama. Cocok untuk foto lereng atau alat sensor.',
            'ukuran' => self::HERO_BANNER,
            'rasio' => '3/1',
            'alt' => '',
        ],
        'edukasi.hero' => [
            'nama' => 'Latar judul Edukasi',
            'halaman' => 'Edukasi',
            'penjelasan' => 'Gambar latar di bagian judul halaman Edukasi. Cocok untuk foto kegiatan belajar, sosialisasi mitigasi, atau pengolahan limbah.',
            'ukuran' => self::HERO_BANNER,
            'rasio' => '3/1',
            'alt' => '',
        ],
        'toko.hero' => [
            'nama' => 'Latar judul Toko Desa',
            'halaman' => 'Toko Desa',
            'penjelasan' => 'Gambar latar di bagian judul halaman Toko Desa (pilihan marketplace). Cocok untuk foto produk kopi atau jamur yang ditata rapi.',
            'ukuran' => self::HERO_BANNER,
            'rasio' => '3/1',
            'alt' => '',
        ],
        'hasil.hero' => [
            'nama' => 'Latar judul Hasil Panen',
            'halaman' => 'Hasil Panen',
            'penjelasan' => 'Gambar latar di bagian judul halaman Hasil Panen / History Limbah. Cocok untuk foto panen kopi atau tumpukan cangkang kopi.',
            'ukuran' => self::HERO_BANNER,
            'rasio' => '3/1',
            'alt' => '',
        ],
        'masuk.latar' => [
            'nama' => 'Latar halaman Masuk admin',
            'halaman' => 'Masuk Admin',
            'penjelasan' => 'Gambar latar penuh di halaman Masuk admin, di belakang kotak isian email dan kata sandi.',
            'ukuran' => '1920 × 1080 px (mendatar, 16:9)',
            'rasio' => '16/9',
            'alt' => '',
        ],
    ];

    /**
     * @return array{nama: string, halaman: string, penjelasan: string, ukuran: string, rasio: string, alt: string}|null
     */
    public static function ambil(string $kunci): ?array
    {
        return self::SEMUA[$kunci] ?? null;
    }

    // Nama berkas bawaan mengikuti kunci (beranda.hero → beranda-hero.svg) agar mudah dicari.
    public static function bawaan(string $kunci): string
    {
        return 'images/default/'.str_replace('.', '-', $kunci).'.svg';
    }

    /**
     * Memastikan setiap slot di daftar punya baris di basis data. Nama, penjelasan, dan ukuran
     * selalu diperbarui dari kode; gambar unggahan dan teks alt pilihan admin tidak disentuh.
     */
    public static function sinkronkan(): void
    {
        $urutan = 0;

        foreach (self::SEMUA as $kunci => $isi) {
            $slot = SlotGambar::query()->firstOrNew(['kunci' => $kunci]);
            $baru = ! $slot->exists;

            $slot->fill([
                'nama' => $isi['nama'],
                'halaman' => $isi['halaman'],
                'urutan' => $urutan++,
                'penjelasan' => $isi['penjelasan'],
                'ukuran_saran' => $isi['ukuran'],
                'rasio' => $isi['rasio'],
                'gambar_bawaan' => self::bawaan($kunci),
            ]);

            if ($baru) {
                $slot->alt = $isi['alt'];
            }

            $slot->save();
        }
    }
}
