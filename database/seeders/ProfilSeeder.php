<?php

namespace Database\Seeders;

use App\Models\Profil\Berita;
use App\Models\Profil\Foto;
use App\Models\Profil\Lembaga;
use App\Models\Profil\Pengaturan;
use App\Models\Profil\Perangkat;
use App\Models\Profil\Potensi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * DATA CONTOH. Nama orang, angka, dan berita di sini karangan untuk mengisi tampilan
 * sementara. Ganti semuanya lewat panel admin (menu Profil Desa dan Berita & Artikel)
 * sebelum situs dipakai warga. Seeder ini aman dijalankan ulang: data yang sudah ada
 * atau sudah diubah admin tidak ditimpa.
 */
class ProfilSeeder extends Seeder
{
    public function run(): void
    {
        $this->isiPengaturan();
        $this->isiPerangkat();
        $this->isiLembaga();
        $this->isiBerita();
        $this->isiGaleri();
        $this->isiPotensi();
        $this->isiFotoPotensi();
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

    private function isiPengaturan(): void
    {
        Pengaturan::isiBilaKosong([
            'tagline' => 'Desa sejuk di lereng Paninggaran, tangguh menghadapi bencana',
            'nama_kepala_desa' => 'Bapak Slamet Riyadi',
            'sambutan' => "Assalamu'alaikum warahmatullahi wabarakatuh.\n\nPuji syukur kami panjatkan ke hadirat Allah SWT. Melalui situs ini, Pemerintah Desa Lumeneng ingin lebih dekat dengan warga dan siapa pun yang ingin mengenal desa kami.\n\nDi sini Bapak/Ibu dapat membaca kabar terbaru, mengenal perangkat desa, memantau peringatan dini longsor, hingga melihat produk unggulan warga seperti kopi dan jamur tiram. Mari bersama-sama menjaga desa agar tetap aman, rukun, dan sejahtera.\n\nWassalamu'alaikum warahmatullahi wabarakatuh.",
            'sejarah' => "Menurut cerita para sesepuh, nama Lumeneng berasal dari kata \"lumeneng\" yang berarti tenang dan teduh, menggambarkan suasana perbukitan hijau yang sejuk di wilayah ini.\n\nSejak dahulu warga hidup dari bertani di lereng bukit: menanam kopi, sayuran, dan padi. Tradisi gotong royong seperti kerja bakti dan sambatan masih terjaga hingga sekarang.\n\nKarena berada di daerah perbukitan dengan curah hujan tinggi, warga Lumeneng belajar hidup berdampingan dengan risiko longsor. Bersama Karang Taruna Giri, desa kini membangun sistem peringatan dini agar warga dapat bersiap lebih awal.",
            'visi' => 'Terwujudnya Desa Lumeneng yang aman, mandiri, dan sejahtera berlandaskan gotong royong dan kearifan lokal.',
            'misi' => "Meningkatkan pelayanan pemerintahan desa yang cepat, terbuka, dan ramah warga.\nMembangun kesiapsiagaan warga menghadapi bencana tanah longsor.\nMengembangkan ekonomi warga melalui kopi, jamur tiram, dan produk olahan desa.\nMenjaga kelestarian lingkungan dan mengolah limbah pertanian menjadi bernilai.\nMemperkuat peran pemuda, perempuan, dan lembaga desa dalam pembangunan.",
            // Data wilayah dari Kajian Pemetaan Bencana Desa Lumeneng 2024 (BIG 2023; kependudukan per Desember 2022).
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
            'alamat_kantor' => 'Jl. Raya Lumeneng No. 1, Kec. Paninggaran, Kab. Pekalongan, Jawa Tengah',
            'telepon' => '0812-0000-0000',
            'email' => 'pemdes.lumeneng@contoh.id',
            'jam_pelayanan' => "Senin–Kamis: 08.00–15.00 WIB\nJumat: 08.00–11.00 WIB\nSabtu, Minggu, dan hari libur: tutup",
        ]);
    }

    private function isiPerangkat(): void
    {
        $daftar = [
            ['Bapak Slamet Riyadi', 'Kepala Desa', 'kepala', 1],
            ['Sri Wahyuni, S.E.', 'Sekretaris Desa', 'sekretaris', 1],
            ['Ahmad Fauzi', 'Kaur Keuangan', 'kaur', 1],
            ['Siti Aminah', 'Kaur Umum dan Perencanaan', 'kaur', 2],
            ['Budi Santoso', 'Kasi Pemerintahan', 'kasi', 1],
            ['Nur Hidayah', 'Kasi Kesejahteraan', 'kasi', 2],
            ['Teguh Prasetyo', 'Kasi Pelayanan', 'kasi', 3],
            ['Sugiarto', 'Kepala Dusun Krajan', 'kadus', 1],
            ['Wahyudi', 'Kepala Dusun Sigemplong', 'kadus', 2],
            ['Mulyono', 'Kepala Dusun Karanganyar', 'kadus', 3],
            ['Rohmat', 'Kepala Dusun Kalipucung', 'kadus', 4],
        ];

        foreach ($daftar as [$nama, $jabatan, $kelompok, $urutan]) {
            Perangkat::query()->firstOrCreate(['jabatan' => $jabatan], compact('nama', 'kelompok', 'urutan'));
        }
    }

    private function isiLembaga(): void
    {
        $daftar = [
            ['Badan Permusyawaratan Desa', 'BPD', 'Bapak Darsono', 'Menampung aspirasi warga dan bersama kepala desa menyusun peraturan desa.'],
            ['Lembaga Pemberdayaan Masyarakat Desa', 'LPMD', 'Bapak Kusnadi', 'Membantu perencanaan dan pelaksanaan pembangunan desa.'],
            ['Pemberdayaan Kesejahteraan Keluarga', 'PKK', 'Ibu Sri Wahyuni', 'Menggerakkan ibu-ibu dalam kesehatan keluarga, posyandu, dan ekonomi rumah tangga.'],
            ['Karang Taruna Giri', 'KT', 'Mas Arif Hidayat', 'Wadah pemuda desa; 25 anggotanya menjadi kader tanggap bencana longsor.'],
            ['Perlindungan Masyarakat', 'Linmas', 'Bapak Suparno', 'Menjaga ketertiban dan membantu warga saat terjadi bencana.'],
            ['Pos Pelayanan Terpadu', 'Posyandu', 'Ibu Nur Hidayah', 'Pelayanan kesehatan ibu, bayi, balita, dan lansia setiap bulan.'],
        ];

        foreach ($daftar as $i => [$nama, $singkatan, $ketua, $keterangan]) {
            Lembaga::query()->firstOrCreate(['nama' => $nama], compact('singkatan', 'ketua', 'keterangan') + ['urutan' => $i + 1]);
        }
    }

    private function isiBerita(): void
    {
        $daftar = [
            ['berita', 3, 'Kader Karang Taruna Giri Ikuti Pelatihan Tanggap Bencana Longsor',
                'Sebanyak 25 pemuda Karang Taruna Giri belajar membaca tanda-tanda longsor dan cara mengevakuasi warga.',
                "Sebanyak 25 pemuda anggota Karang Taruna Giri mengikuti pelatihan tanggap bencana longsor di balai desa. Pelatihan ini diadakan bersama mahasiswa KKN.\n\nPeserta belajar mengenali tanda awal longsor, seperti retakan tanah, pohon atau tiang yang miring, serta air sumur yang tiba-tiba keruh. Mereka juga berlatih menentukan jalur dan titik kumpul evakuasi.\n\n\"Kami ingin setiap dusun punya kader yang siap membantu warga saat hujan deras,\" ujar ketua Karang Taruna.\n\nSetelah pelatihan, para kader akan bergiliran memantau dasbor peringatan dini di situs desa."],
            ['pengumuman', 1, 'Jadwal Posyandu Balita dan Lansia Bulan Ini',
                'Posyandu balita dan lansia dilaksanakan serentak di setiap dusun. Bawa buku KIA dan kartu berobat.',
                "Diberitahukan kepada seluruh warga bahwa kegiatan Posyandu balita dan lansia akan dilaksanakan serentak di setiap dusun mulai pukul 08.00 WIB.\n\nMohon membawa buku KIA untuk balita dan kartu berobat untuk lansia. Akan ada penimbangan, pemberian vitamin, dan pemeriksaan tekanan darah.\n\nInformasi lebih lanjut dapat ditanyakan kepada kader Posyandu di dusun masing-masing."],
            ['artikel', 6, 'Kenali Tanda-Tanda Tanah Akan Longsor di Sekitar Rumah',
                'Retakan tanah, pohon miring, dan mata air baru bisa menjadi tanda bahaya. Kenali sejak dini agar keluarga selamat.',
                // Contoh isi berformat dari editor: judul kecil, daftar, tebal, dan kutipan.
                '<p>Longsor sering terjadi setelah <strong>hujan deras berhari-hari</strong>. Namun tanah biasanya memberi tanda sebelum bergerak.</p>'
                .'<h3>Tanda-tanda yang perlu diwaspadai</h3>'
                .'<ul><li>Retakan baru di tanah atau dinding rumah.</li><li>Pintu dan jendela yang tiba-tiba sulit dibuka.</li><li>Pohon atau tiang listrik yang <em>miring</em>.</li><li>Rembesan atau mata air baru di lereng.</li></ul>'
                .'<h3>Yang harus dilakukan</h3>'
                .'<ol><li>Segera beri tahu ketua RT atau kader Karang Taruna.</li><li>Jauhi lereng, terutama saat hujan.</li><li>Pantau status peringatan dini di situs desa.</li></ol>'
                .'<blockquote>Informasi di situs desa adalah alat bantu, bukan peringatan resmi BPBD.</blockquote>'],
            ['berita', 9, 'Panen Kopi Warga Meningkat, Limbah Kulit Kopi Diolah Jadi Media Jamur',
                'Petani kopi Lumeneng mulai memanfaatkan kulit kopi sebagai bahan baglog jamur tiram.',
                "Musim panen kopi tahun ini membawa kabar baik bagi petani Lumeneng. Selain hasil panen yang meningkat, limbah kulit kopi kini tidak lagi dibuang.\n\nBersama kelompok petani muda, kulit kopi dicampur serbuk kayu untuk dijadikan baglog atau media tanam jamur tiram. Jamur yang dihasilkan dijual ke pasar dan lewat toko desa daring.\n\nCatatan panen dan limbah dapat dilihat warga di halaman History Limbah."],
            ['pengumuman', 12, 'Kerja Bakti Membersihkan Saluran Air Menjelang Musim Hujan',
                'Seluruh warga diajak kerja bakti membersihkan saluran air agar air hujan tidak meresap ke lereng.',
                "Menjelang musim hujan, pemerintah desa mengajak seluruh warga mengikuti kerja bakti membersihkan saluran air di lingkungan masing-masing pada hari Minggu pukul 07.00 WIB.\n\nSaluran yang tersumbat membuat air hujan meresap ke tanah lereng dan meningkatkan risiko longsor. Mohon membawa cangkul, sabit, dan karung.\n\nTerima kasih atas partisipasi Bapak/Ibu sekalian."],
            ['artikel', 15, 'Tips Menyimpan Biji Kopi Agar Tetap Harum',
                'Biji kopi yang disimpan dengan benar lebih tahan lama dan harganya lebih baik.',
                "Simpan biji kopi yang sudah kering dalam karung bersih atau wadah kedap udara.\n\nLetakkan di tempat yang sejuk, kering, dan tidak terkena sinar matahari langsung. Beri alas kayu agar karung tidak menyentuh lantai yang lembap.\n\nHindari menyimpan kopi di dekat bahan berbau tajam seperti minyak tanah atau bawang, karena biji kopi mudah menyerap bau."],
        ];

        foreach ($daftar as [$jenis, $hariLalu, $judul, $ringkasan, $isi]) {
            Berita::query()->firstOrCreate(['slug' => Str::slug($judul)], [
                'jenis' => $jenis,
                'judul' => $judul,
                'ringkasan' => $ringkasan,
                'isi' => $isi,
                'penulis' => 'Admin Desa',
                'diterbitkan_pada' => now()->subDays($hariLalu)->setTime(9, 0),
            ]);
        }
    }

    /**
     * Foto contoh: hanya foto produk desa yang sudah ada di public/gambar/toko. Semuanya diberi
     * judul "(contoh)"; ganti dengan foto asli lewat formulir berita dan menu Galeri.
     */
    private function isiGaleri(): void
    {
        $kopi = $this->beritaDenganSlug('panen-kopi-warga-meningkat-limbah-kulit-kopi-diolah-jadi-media-jamur');

        $this->pasangSampul($kopi, $this->salinFotoDesa('kopi-puntang.jpg'));

        $daftar = [
            [$kopi, $this->salinFotoDesa('baglog-koci.jpg'), 'Baglog dari campuran kulit kopi (contoh)', 'Kulit kopi sisa panen dicampur serbuk kayu menjadi media tanam jamur tiram.'],
            [$kopi, $this->salinFotoDesa('jamur-tiram.jpg'), 'Jamur tiram siap panen (contoh)', 'Jamur tumbuh dari baglog kulit kopi dan siap dijual warga.'],
            [$kopi, $this->salinFotoDesa('kompos-sirkular.jpg'), 'Sisa baglog menjadi kompos (contoh)', 'Baglog bekas panen dihancurkan dan dijadikan pupuk kebun.'],
            [null, $this->salinFotoDesa('kopi-puntang.jpg', 'kebun-kopi'), 'Kopi hasil kebun warga (contoh)', 'Biji kopi pilihan dari kebun di lereng desa.'],
        ];

        foreach ($daftar as $i => [$berita, $gambar, $judul, $keterangan]) {
            if ($gambar === null) {
                continue;
            }

            Foto::query()->firstOrCreate(['gambar' => $gambar], [
                'berita_id' => $berita?->id,
                'judul' => $judul,
                'keterangan' => $keterangan,
                'urutan' => $i + 1,
                'tanggal' => $berita ? null : now()->subDays(20 + $i)->toDateString(),
            ]);
        }
    }

    private function beritaDenganSlug(string $slug): ?Berita
    {
        return Berita::query()->where('slug', $slug)->first();
    }

    // Sampul hanya dipasang bila berita belum punya foto, agar foto pilihan admin tidak tertimpa.
    private function pasangSampul(?Berita $berita, ?string $gambar): void
    {
        if ($berita && $gambar && blank($berita->gambar)) {
            $berita->update(['gambar' => $gambar]);
        }
    }

    private function salinFotoDesa(string $berkas, ?string $nama = null): ?string
    {
        $asal = public_path('gambar/toko/'.$berkas);
        $tujuan = 'profil/contoh/'.($nama ?? pathinfo($berkas, PATHINFO_FILENAME)).'.jpg';

        if (! is_file($asal)) {
            return null;
        }

        if (! Storage::disk('public')->exists($tujuan)) {
            Storage::disk('public')->put($tujuan, (string) file_get_contents($asal));
        }

        return $tujuan;
    }
}
