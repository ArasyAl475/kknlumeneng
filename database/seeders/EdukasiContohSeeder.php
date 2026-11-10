<?php

namespace Database\Seeders;

use App\Models\Edukasi\Kategori;
use App\Models\Edukasi\Materi;
use App\Services\Edukasi\TautanYoutube;
use Illuminate\Database\Seeder;

/**
 * Data contoh modul Edukasi untuk latihan, uji tampilan, dan presentasi.
 *
 * Dijalankan manual (tidak ikut DatabaseSeeder): php artisan db:seed --class=EdukasiContohSeeder
 * Aman diulang: materi dicari lewat slug, langkahnya ditulis ulang. Menolak jalan di produksi
 * agar materi contoh tidak tampil ke warga sungguhan.
 */
class EdukasiContohSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Data contoh Edukasi tidak dijalankan di produksi.');

            return;
        }

        // Kategori dan tips sirkularitas awal dibuat oleh seeder utama modul.
        $this->call(EdukasiSeeder::class);

        $kategori = Kategori::query()->pluck('id', 'slug');
        $semua = self::daftarMateri();

        // Urutan array = urutan belajar: tiap kategori dinomori Bagian 1, 2, 3 sesuai posisinya di daftar.
        $nomorBagian = [];

        foreach ($semua as $urutan => $data) {
            $nomorBagian[$data['kategori']] = ($nomorBagian[$data['kategori']] ?? 0) + 1;

            $materi = Materi::query()->firstOrNew(['slug' => $data['slug']]);
            $materi->fill([
                'kategori_id' => $kategori[$data['kategori']],
                'urutan' => $nomorBagian[$data['kategori']],
                'judul' => $data['judul'],
                'ringkasan' => $data['ringkasan'],
                'penjelasan' => $data['penjelasan'] ?? null,
                'video_youtube' => TautanYoutube::kode($data['video'] ?? null),
            ]);
            // Tanggal terbit hanya diisi sekali dan dibuat berselisih menit, agar urutan di daftar tetap stabil.
            $materi->diterbitkan_pada ??= now()->subMinutes(count($semua) - $urutan);
            $materi->save();

            $materi->langkah()->delete();
            foreach ($data['langkah'] as $nomor => [$judul, $isi]) {
                $materi->langkah()->create(['urutan' => $nomor + 1, 'judul' => $judul, 'isi' => $isi]);
            }
        }
    }

    /**
     * Tautan video diambil dari hasil pencarian YouTube; isinya belum diperiksa satu per satu,
     * jadi tonton dulu sebelum dipakai di depan warga. Materi tanpa 'video' sengaja dibiarkan kosong.
     *
     * @return list<array<string, mixed>>
     */
    private static function daftarMateri(): array
    {
        return [
            [
                'kategori' => 'jamur',
                'slug' => 'mengenal-budidaya-jamur-tiram',
                'judul' => 'Mengenal Budidaya Jamur Tiram',
                'ringkasan' => 'Gambaran awal usaha jamur tiram: bahan, tempat, dan modal yang dibutuhkan sebelum mulai.',
                'video' => 'https://www.youtube.com/watch?v=XG-rECFCabY',
                'penjelasan' => "Jamur tiram cocok dibudidayakan warga karena bahan dasarnya mudah didapat, yaitu serbuk gergaji, bekatul, dan kapur. Tempatnya cukup ruangan teduh yang lembap.\n\nTonton video di atas untuk melihat contoh usaha jamur skala rumah, lalu ikuti langkah awal di bawah.",
                'langkah' => [
                    ['Siapkan tempat budidaya', 'Pilih ruangan teduh yang tidak terkena sinar matahari langsung, punya ventilasi, dan mudah dibersihkan. Tempat ini disebut kumbung. Baglog diletakkan di rak bersusun.'],
                    ['Siapkan bahan', 'Kumpulkan serbuk gergaji kayu, bekatul, kapur pertanian, dan kantong plastik tahan panas. Bibit jamur dibeli dari pembuat bibit yang terpercaya.'],
                    ['Mulai dari jumlah kecil', 'Mulailah dari sekitar 100 baglog agar mudah dirawat dan hasilnya bisa dipelajari. Tambah jumlahnya setelah panen pertama berhasil.'],
                ],
            ],
            [
                'kategori' => 'jamur',
                'slug' => 'cara-merawat-jamur-tiram',
                'judul' => 'Cara Merawat Jamur Tiram Sampai Panen',
                'ringkasan' => 'Atur kelembapan, suhu, dan penyiraman supaya baglog menghasilkan jamur yang sehat.',
                'video' => 'https://www.youtube.com/watch?v=qeJFKKtB37U',
                'penjelasan' => 'Setelah miselium (benang putih jamur) memenuhi baglog, jamur tinggal dirawat di kumbung. Kuncinya menjaga ruangan tetap lembap, teduh, dan berudara segar.',
                'langkah' => [
                    ['Jaga ruangan tetap lembap', 'Siram lantai dan dinding kumbung dengan percikan halus agar udara lembap. Jangan menyiram deras ke jamur yang masih kecil.'],
                    ['Atur suhu dan udara', 'Jamur tiram menyukai suhu sekitar 22 sampai 28 derajat Celsius. Buka ventilasi pada pagi hari agar udara segar masuk, tetapi hindari angin kencang.'],
                    ['Buka penutup baglog', 'Ketika calon jamur mulai tampak, buka sumbat atau cincin baglog agar jamur dapat tumbuh keluar.'],
                    ['Periksa setiap hari', 'Cek baglog tiap pagi. Pisahkan baglog yang berwarna hijau atau hitam, atau berbau busuk, karena itu tanda terkena jamur liar.'],
                ],
            ],
            [
                'kategori' => 'jamur',
                'slug' => 'panen-dan-penyimpanan-jamur-tiram',
                'judul' => 'Panen dan Penyimpanan Jamur Tiram',
                'ringkasan' => 'Kapan memanen, cara memetik, dan cara menyimpan jamur agar tetap segar dan laku dijual.',
                'langkah' => [
                    ['Panen pada waktu yang tepat', 'Petik saat tudung jamur sudah mekar, tetapi tepinya belum terlalu melebar atau pecah-pecah.'],
                    ['Petik dengan benar', 'Pegang rumpun dari pangkalnya, putar pelan, lalu tarik. Bersihkan sisa akar yang menempel di baglog.'],
                    ['Simpan dengan baik', 'Jangan dicuci dulu. Simpan di wadah berongga di tempat sejuk, dan jual atau olah secepatnya, sebaiknya di hari yang sama.'],
                    ['Rapikan baglog', 'Buang sisa panen yang tertinggal di permukaan baglog agar tidak membusuk dan menarik hama.'],
                ],
            ],
            [
                'kategori' => 'baglog',
                'slug' => 'cara-membuat-baglog-jamur-tiram',
                'judul' => 'Cara Membuat Baglog Jamur Tiram untuk Pemula',
                'ringkasan' => 'Mencampur media, mengisi plastik, dan memadatkannya langkah demi langkah.',
                'video' => 'https://www.youtube.com/watch?v=QaXOLcli5n4',
                'penjelasan' => "Baglog adalah media tanam jamur berbentuk kantong plastik berisi campuran serbuk gergaji, bekatul, dan kapur.\n\nTakaran yang umum dipakai kira-kira 100 bagian serbuk gergaji, 10 bagian bekatul, dan 2 bagian kapur. Takaran bisa berbeda, jadi sesuaikan dengan petunjuk penyuluh atau pembuat bibit.",
                'langkah' => [
                    ['Siapkan serbuk gergaji', 'Ayak serbuk gergaji untuk membuang kayu yang kasar atau benda asing, lalu tumpuk di lantai yang bersih.'],
                    ['Campur bekatul dan kapur', 'Taburkan bekatul dan kapur di atas serbuk gergaji, lalu aduk sampai warnanya rata.'],
                    ['Tambahkan air', 'Tambahkan air sedikit demi sedikit sampai kadar air sekitar 60 persen. Tesnya: genggam adonan, harus menggumpal tetapi tidak menetes.'],
                    ['Isi dan padatkan', 'Masukkan adonan ke kantong plastik sambil ditekan sampai padat dan tidak ada ruang kosong.'],
                    ['Pasang cincin dan sumbat', 'Pasang cincin di mulut kantong lalu tutup dengan sumbat kapas atau kertas supaya udara tetap bisa masuk tetapi kotoran tidak.'],
                ],
            ],
            [
                'kategori' => 'baglog',
                'slug' => 'mengukus-baglog-jamur-tiram',
                'judul' => 'Mengukus (Sterilisasi) Baglog',
                'ringkasan' => 'Mengukus baglog agar bebas jamur liar dan bakteri sebelum diberi bibit.',
                'video' => 'https://www.youtube.com/watch?v=io-4UrVrXwU',
                'penjelasan' => 'Pengukusan membunuh jamur liar dan bakteri di dalam baglog. Tahap ini wajib karena baglog yang tidak steril mudah gagal tumbuh. Hati-hati dengan uap panas dan api, dan jauhkan dari anak-anak.',
                'langkah' => [
                    ['Susun baglog di drum', 'Susun baglog berdiri dengan sedikit jarak agar uap merata. Isi air drum secukupnya dan pastikan air tidak menyentuh baglog.'],
                    ['Kukus selama 6 sampai 8 jam', 'Hitung waktu sejak uap keluar stabil. Tambahkan air bila hampir habis agar drum tidak kering.'],
                    ['Dinginkan semalam', 'Jangan membuka baglog saat masih panas. Biarkan dingin di ruangan yang bersih.'],
                    ['Beri bibit', 'Setelah dingin, masukkan bibit di ruangan bersih lalu simpan baglog di ruang inkubasi yang gelap.'],
                ],
            ],
            [
                'kategori' => 'baglog',
                'slug' => 'memanfaatkan-baglog-bekas-panen',
                'judul' => 'Memanfaatkan Baglog Bekas Panen',
                'ringkasan' => 'Baglog yang sudah tidak berproduksi masih bisa menjadi kompos dan pupuk kebun.',
                'penjelasan' => 'Baglog yang sudah tidak menghasilkan jamur bukan sampah. Isinya kaya serat kayu dan sisa nutrisi sehingga cocok dijadikan kompos untuk kebun.',
                'langkah' => [
                    ['Kumpulkan baglog bekas', 'Kumpulkan baglog yang sudah tidak berproduksi di satu tempat, jauh dari baglog yang masih sehat.'],
                    ['Pisahkan plastiknya', 'Keluarkan media dari plastik. Plastik dikumpulkan dan jangan dibakar karena asapnya berbahaya.'],
                    ['Hancurkan dan campur', 'Hancurkan media lalu campur dengan kotoran ternak atau sisa dedaunan dengan perbandingan kira-kira satu banding satu.'],
                    ['Tutup dan balik berkala', 'Tutup tumpukan dengan terpal, lalu balik setiap satu sampai dua minggu. Kompos siap dipakai setelah sekitar satu sampai dua bulan, saat warnanya gelap dan tidak berbau menyengat.'],
                ],
            ],
            [
                'kategori' => 'pengolahan-limbah-kopi',
                'slug' => 'mengolah-kulit-kopi-menjadi-kompos',
                'judul' => 'Mengolah Kulit Kopi Menjadi Kompos',
                'ringkasan' => 'Kulit kopi hasil panen dijadikan kompos supaya limbah kebun kembali menyuburkan tanah.',
                'video' => 'https://www.youtube.com/watch?v=U3WorUylkIc',
                'penjelasan' => 'Kulit kopi (cangkang) menumpuk setiap musim panen. Jika dibuang begitu saja, ia mengganggu lingkungan. Jika dikomposkan, ia menjadi pupuk yang menyuburkan kebun. Jangan menaburkan kulit kopi segar langsung ke tanaman karena sifatnya asam.',
                'langkah' => [
                    ['Kumpulkan kulit kopi', 'Kumpulkan kulit kopi segar atau yang sudah dijemur di lokasi pengomposan yang teduh dan tidak tergenang.'],
                    ['Campur dengan bahan lain', 'Campur kulit kopi dengan kotoran ternak atau daun kering dengan perbandingan kira-kira satu banding satu.'],
                    ['Basahi dan tutup', 'Basahi sampai lembap seperti spons yang diperas, lalu tutup dengan terpal agar tidak kehujanan dan tidak kering.'],
                    ['Balik secara rutin', 'Balik tumpukan setiap satu sampai dua minggu. Kompos matang berwarna gelap, remah, dan tidak berbau menyengat, biasanya setelah satu sampai dua bulan.'],
                ],
            ],
            [
                'kategori' => 'pengolahan-limbah-kopi',
                'slug' => 'kulit-kopi-untuk-pupuk-dan-pakan-ternak',
                'judul' => 'Kulit Kopi untuk Pupuk dan Pakan Ternak',
                'ringkasan' => 'Contoh program desa yang mengubah limbah kulit kopi menjadi pupuk dan pakan ternak.',
                'video' => 'https://www.youtube.com/watch?v=mYxY93vH4QA',
                'penjelasan' => 'Video di atas adalah contoh program pemberdayaan masyarakat yang mengolah kulit kopi menjadi pupuk dan pakan ternak. Gunakan sebagai gambaran, lalu sesuaikan dengan kondisi kebun dan ternak di desa.',
                'langkah' => [
                    ['Pelajari contoh programnya', 'Tonton videonya sampai selesai dan catat bahan serta alat yang dipakai.'],
                    ['Kumpulkan dan jemur kulit kopi', 'Jemur kulit kopi sampai kering agar tidak berjamur dan lebih mudah disimpan.'],
                    ['Konsultasikan untuk pakan ternak', 'Kulit kopi mengandung kafein dan tanin, jadi pemberiannya ke ternak harus dibatasi. Tanyakan takaran yang aman kepada penyuluh peternakan sebelum dipakai.'],
                    ['Mulai dari skala kecil', 'Coba dengan jumlah kecil dan pada beberapa ternak dulu. Perhatikan nafsu makan dan kesehatannya sebelum dilanjutkan.'],
                ],
            ],
            [
                'kategori' => 'pengolahan-limbah-kopi',
                'slug' => 'kulit-kopi-untuk-media-baglog',
                'judul' => 'Kulit Kopi sebagai Campuran Media Baglog',
                'ringkasan' => 'Kulit kopi bisa menggantikan sebagian serbuk gergaji, tetapi perlu dikomposkan lebih dulu.',
                'penjelasan' => 'Limbah kulit kopi dapat dipakai sebagai bahan campuran baglog jamur sehingga limbah kebun menjadi bahan baru. Cara ini masih perlu dicoba dalam jumlah kecil karena hasilnya dipengaruhi jenis kopi dan cara pengolahannya.',
                'langkah' => [
                    ['Komposkan dulu', 'Kulit kopi segar sebaiknya dikomposkan terlebih dahulu sampai tidak lagi berbau asam menyengat.'],
                    ['Campur sedikit demi sedikit', 'Ganti sebagian kecil serbuk gergaji dengan kulit kopi yang sudah matang. Mulailah dengan porsi kecil dan catat takarannya.'],
                    ['Uji dalam jumlah kecil', 'Buat beberapa baglog percobaan lebih dulu. Bandingkan pertumbuhan miselium dan hasil panennya dengan baglog biasa sebelum dipakai dalam jumlah besar.'],
                ],
            ],
        ];
    }
}
