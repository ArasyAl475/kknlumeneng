<?php

namespace Database\Seeders;

use App\Models\Profil\Foto;
use App\Models\Profil\Pengaturan;
use App\Models\Profil\Potensi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * DATA ASLI untuk Profil Desa, dari Kajian Pemetaan Bencana Desa Lumeneng 2024 (LPBI NU Kab. Pekalongan
 * dan Ringan Tangan Indonesia): angka wilayah, batas desa, serta potensi desa beserta fotonya.
 * Aman dijalankan di server dan aman diulang: data yang sudah ada atau sudah diubah admin tidak ditimpa.
 *
 * Isi yang belum ada di dokumen (nama kepala desa, sambutan, sejarah, visi misi, kontak, perangkat, lembaga,
 * berita) TIDAK diisi di sini; admin mengisinya lewat panel. Data karangan untuk latihan ada di
 * ProfilContohSeeder, yang hanya ikut berjalan bila config('seeder.contoh') aktif (lokal, bukan server).
 */
class ProfilSeeder extends Seeder
{
    public function run(): void
    {
        $this->isiDataWilayah();
        $this->isiPotensi();
        $this->isiFotoPotensi();

        if (config('seeder.contoh')) {
            $this->call(ProfilContohSeeder::class);
        }
    }

    private function isiDataWilayah(): void
    {
        Pengaturan::isiBilaKosong([
            // BIG 2023; kependudukan per Desember 2022.
            'luas_wilayah' => '884.6',
            'ketinggian' => '540 – 1350',
            'jumlah_penduduk' => '3872',
            'jumlah_kk' => '1041',
            'jumlah_dusun' => '6',
            'jumlah_rw' => '6',
            'jumlah_rt' => '18',
            'batas_utara' => 'Desa Paninggaran',
            'batas_selatan' => 'Desa Trajumas dan Desa Bodas',
            'batas_timur' => 'Desa Kaliboja dan Desa Tanggeran',
            'batas_barat' => 'Desa Krandegan',
        ]);
    }

    /**
     * Potensi desa dari Kajian Pemetaan Bencana Desa Lumeneng 2024 (bab Profil dan Sumberdaya).
     * Hanya diisi bila tabel masih kosong, supaya perubahan admin tidak tertimpa.
     */
    private function isiPotensi(): void
    {
        if (Potensi::query()->exists()) {
            return;
        }

        $daftar = [
            [
                'nama' => 'Hutan lindung',
                'kategori' => 'hutan',
                'luas' => '610,03 ha (69% wilayah desa)',
                'hasil' => 'Pinus, rotan, bambu',
                'lokasi' => 'Perbukitan desa, berbatasan langsung dengan kebun warga',
                'keterangan' => 'Lebih dari separuh wilayah desa adalah hutan lindung yang dikelola Perhutani. Hutan ini menyerap karbon dan menjadi daerah tangkapan air bagi mata air desa.',
            ],
            [
                'nama' => 'Kebun campuran',
                'kategori' => 'perkebunan',
                'luas' => '153,33 ha',
                'hasil' => 'Manggis, durian, alpukat, kelapa, cengkeh, kopi, teh, pisang, duku, kapulaga, sengon, bambu',
                'lokasi' => 'Kebun warga di sekitar permukiman dan hutan lindung',
                'keterangan' => 'Kebun ditanami tanaman kayu dan buah yang bernilai ekonomi. Manggis, durian, dan alpukat menjadi pendapatan musiman, sedangkan kelapa bisa dipanen kapan saja dan dijual saat hari pasaran.',
            ],
            [
                'nama' => 'Sawah terasering',
                'kategori' => 'pertanian',
                'luas' => '70,85 ha',
                'hasil' => 'Padi (tiga kali tanam setahun)',
                'lokasi' => 'Bagian utara desa yang landai, di sekitar permukiman',
                'keterangan' => 'Terasering memanfaatkan lahan miring sekaligus menahan erosi. Panen terutama untuk pangan keluarga, sebagian dijual. Curah hujan tinggi membuat produktivitas padi rendah.',
            ],
            [
                'nama' => 'Mata air desa',
                'kategori' => 'air',
                'luas' => '14 mata air',
                'hasil' => 'Air bersih, air irigasi',
                'lokasi' => 'Hutan lindung dan dusun-dusun: Wanasida, Krajan, Kulon, Kaliwisnu, Sumingkir, Sikembang',
                'keterangan' => 'Air bersih warga berasal dari mata air di kawasan hutan, ditampung di bak dekat sungai, lalu dialirkan ke rumah dengan sistem gravitasi. Hasil uji air di enam dusun: TDS 34–60 dan pH 7,8–8,6.',
            ],
            [
                'nama' => 'Kali Genteng',
                'kategori' => 'air',
                'luas' => null,
                'hasil' => 'Air irigasi',
                'lokasi' => 'Dusun V Kaliwisnu',
                'keterangan' => 'Salah satu aliran air utama desa yang dimanfaatkan untuk mengairi sawah di Kaliwisnu.',
            ],
        ];

        foreach ($daftar as $urutan => $isi) {
            Potensi::query()->create($isi + ['urutan' => $urutan + 1]);
        }
    }

    /**
     * Foto-foto potensi dari Kajian Pemetaan Bencana Desa Lumeneng 2024 (berkas ikut Git di public/images/lumeneng/potensi).
     * Disalin ke penyimpanan dan dibuat sebagai foto galeri milik potensi itu, sama seperti unggahan admin,
     * jadi otomatis tampil juga di menu Galeri. Hanya potensi yang belum punya foto yang diisi, agar foto pilihan admin tidak tertimpa.
     */
    private function isiFotoPotensi(): void
    {
        $berkas = [
            'Hutan lindung' => ['hutan-lindung-1', 'hutan-lindung-2', 'hutan-lindung-3', 'hutan-lindung-4', 'hutan-lindung-5'],
            'Kebun campuran' => ['kebun-campuran-1', 'kebun-campuran-2', 'kebun-campuran-3', 'kebun-campuran-4'],
            'Sawah terasering' => ['sawah-terasering-1', 'sawah-terasering-2', 'sawah-terasering-3'],
            'Mata air desa' => ['mata-air-1', 'mata-air-2', 'mata-air-3', 'mata-air-4', 'mata-air-5'],
            'Kali Genteng' => ['kali-genteng-1', 'kali-genteng-2', 'kali-genteng-3'],
        ];

        foreach ($berkas as $nama => $daftar) {
            $potensi = Potensi::query()->where('nama', $nama)->first();

            if ($potensi === null || $potensi->fotos()->exists()) {
                continue;
            }

            foreach ($daftar as $i => $kode) {
                $asal = public_path("images/lumeneng/potensi/{$kode}.webp");
                $tujuan = "profil/kajian/{$kode}.webp";

                if (! is_file($asal)) {
                    continue;
                }

                if (! Storage::disk('public')->exists($tujuan)) {
                    Storage::disk('public')->put($tujuan, (string) file_get_contents($asal));
                }

                Foto::query()->create([
                    'potensi_id' => $potensi->id,
                    'gambar' => $tujuan,
                    'judul' => $potensi->nama,
                    'keterangan' => 'Potensi desa: '.$potensi->labelKategori().($potensi->lokasi ? ' · '.$potensi->lokasi : ''),
                    'urutan' => $i + 1,
                ]);
            }
        }
    }
}
