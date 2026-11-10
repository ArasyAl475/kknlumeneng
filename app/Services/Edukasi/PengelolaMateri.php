<?php

namespace App\Services\Edukasi;

use App\Models\Edukasi\Langkah;
use App\Models\Edukasi\Materi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PengelolaMateri
{
    public function __construct(private PengolahGambar $gambar) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function buatMateri(array $data, ?UploadedFile $berkas): Materi
    {
        // Tanpa nomor dari admin, materi baru menjadi bagian terakhir di kategorinya.
        $data['urutan'] ??= ((int) Materi::query()->where('kategori_id', $data['kategori_id'])->max('urutan')) + 1;

        return Materi::query()->create($data + [
            'slug' => PembuatSlug::untuk(Materi::class, $data['judul'], 'materi'),
            'gambar' => $berkas ? $this->gambar->simpan($berkas) : null,
        ]);
    }

    public function hapusMateri(Materi $materi): void
    {
        $gambar = $materi->langkah()->pluck('gambar')->push($materi->gambar)->filter();

        $materi->delete();

        // Berkas dihapus terakhir, agar kegagalan database tidak menyisakan materi tanpa gambar.
        $gambar->each(fn (string $jalur) => $this->gambar->hapus($jalur));
    }

    // Tanggal terbit pertama dipertahankan, agar menerbitkan ulang tidak mengubah urutan daftar.
    public function terbitkan(Materi $materi): void
    {
        $materi->update(['diterbitkan_pada' => $materi->diterbitkan_pada ?? now()]);
    }

    public function tarik(Materi $materi): void
    {
        $materi->update(['diterbitkan_pada' => null]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function tambahLangkah(Materi $materi, array $data, ?UploadedFile $berkas): Langkah
    {
        return $materi->langkah()->create($data + [
            'urutan' => (int) $materi->langkah()->max('urutan') + 1,
            'gambar' => $berkas ? $this->gambar->simpan($berkas) : null,
        ]);
    }

    public function hapusLangkah(Langkah $langkah): void
    {
        $gambar = $langkah->gambar;

        $langkah->delete();
        $this->gambar->hapus($gambar);
    }

    public function pindahLangkah(Langkah $langkah, string $arah): void
    {
        $naik = $arah === 'naik';

        DB::transaction(function () use ($langkah, $naik) {
            // Urutan bisa berlubang setelah ada langkah yang dihapus, jadi tetangga dicari, bukan dihitung.
            $tetangga = Langkah::query()
                ->where('materi_id', $langkah->materi_id)
                ->where('urutan', $naik ? '<' : '>', $langkah->urutan)
                ->orderBy('urutan', $naik ? 'desc' : 'asc')
                ->first();

            if ($tetangga) {
                [$langkah->urutan, $tetangga->urutan] = [$tetangga->urutan, $langkah->urutan];
                $langkah->save();
                $tetangga->save();
            }
        });
    }

    /**
     * Dipakai untuk materi dan langkah. Slug materi sengaja tidak ikut berubah, agar tautan
     * yang sudah dibagikan warga lewat WhatsApp tetap hidup.
     *
     * @param  array<string, mixed>  $data
     */
    public function ubah(Materi|Langkah $model, array $data, ?UploadedFile $berkas, bool $hapusGambar): void
    {
        $lama = $model->gambar;
        $baru = $berkas ? $this->gambar->simpan($berkas) : ($hapusGambar ? null : $lama);

        $model->update($data + ['gambar' => $baru]);

        // Gambar lama dihapus setelah data tersimpan, agar halaman tidak menunjuk berkas yang hilang.
        if ($lama !== $baru) {
            $this->gambar->hapus($lama);
        }
    }
}
