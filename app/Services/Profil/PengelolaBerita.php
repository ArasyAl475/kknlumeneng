<?php

namespace App\Services\Profil;

use App\Models\Profil\Berita;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PengelolaBerita
{
    public function __construct(private PenyimpanGambar $gambar, private PengelolaGaleri $galeri) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $fotoKegiatan  foto dokumentasi yang masuk ke galeri
     */
    public function buat(array $data, ?UploadedFile $gambar, bool $terbit, array $fotoKegiatan = []): Berita
    {
        $berita = Berita::query()->create($data + [
            'slug' => $this->slugUnik($data['judul']),
            'gambar' => $gambar ? $this->gambar->simpan($gambar, 'gambar') : null,
            'diterbitkan_pada' => $terbit ? now() : null,
        ]);

        $this->galeri->tambah($fotoKegiatan, ['berita_id' => $berita->id]);

        return $berita;
    }

    /**
     * Slug sengaja tidak ikut berubah saat judul diganti, agar tautan yang sudah dibagikan
     * warga lewat WhatsApp tetap hidup.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $fotoKegiatan
     */
    public function ubah(Berita $berita, array $data, ?UploadedFile $gambar, bool $hapusGambar, bool $terbit, array $fotoKegiatan = []): void
    {
        $lama = $berita->gambar;
        $baru = $this->gambar->ganti($lama, $gambar, $hapusGambar, 'gambar');

        $berita->update($data + [
            'gambar' => $baru,
            // Tanggal terbit pertama dipertahankan, agar menyimpan ulang tidak mengubah urutan daftar.
            'diterbitkan_pada' => $terbit ? ($berita->diterbitkan_pada ?? now()) : null,
        ]);

        if ($lama !== $baru) {
            $this->gambar->hapus($lama);
        }

        $this->galeri->tambah($fotoKegiatan, ['berita_id' => $berita->id]);
    }

    // Foto dokumentasi ikut terhapus; berkasnya dihapus setelah datanya hilang dari basis data.
    public function hapus(Berita $berita): void
    {
        $berkas = $berita->foto()->pluck('gambar')->push($berita->gambar)->filter();

        $berita->foto()->delete();
        $berita->delete();

        $berkas->each(fn (string $jalur) => $this->gambar->hapus($jalur));
    }

    private function slugUnik(string $judul): string
    {
        // Judul yang seluruhnya simbol menghasilkan slug kosong, padahal alamat tetap harus ada.
        $dasar = rtrim(Str::substr(Str::slug($judul), 0, 150), '-') ?: 'berita';
        $slug = $dasar;
        $nomor = 2;

        while (Berita::query()->where('slug', $slug)->exists()) {
            $slug = "{$dasar}-{$nomor}";
            $nomor++;
        }

        return $slug;
    }
}
