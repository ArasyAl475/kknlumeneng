<?php

namespace Database\Seeders;

use App\Models\Profil\Berita;
use App\Models\Profil\Foto;
use App\Models\Profil\Lembaga;
use App\Models\Profil\Pengaturan;
use App\Models\Profil\Perangkat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * DATA CONTOH. Nama orang, nomor telepon, sambutan, sejarah, visi misi, dan berita di sini karangan untuk
 * mengisi tampilan saat latihan dan uji. JANGAN dipakai di situs yang dilihat warga: isilah lewat panel admin
 * (menu Profil Desa dan Berita & Artikel).
 *
 * Ikut berjalan lewat ProfilSeeder hanya bila config('seeder.contoh') aktif. Nilai awalnya aktif di lokal
 * dan nonaktif di server (APP_ENV=production); ubah dengan SEEDER_DATA_CONTOH di .env bila perlu, misalnya untuk demo.
 * Aman diulang: data yang sudah ada atau sudah diubah admin tidak ditimpa.
 */
class ProfilContohSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('seeder.contoh')) {
            $this->command?->warn('Data contoh Profil tidak dijalankan: SEEDER_DATA_CONTOH nonaktif (nilai awalnya nonaktif di server).');

            return;
        }

        $this->isiPengaturan();
        $this->isiPerangkat();
        $this->isiLembaga();
        $this->isiBerita();
        $this->isiGaleri();
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
