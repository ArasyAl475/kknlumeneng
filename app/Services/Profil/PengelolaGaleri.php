<?php

namespace App\Services\Profil;

use App\Models\Profil\Foto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class PengelolaGaleri
{
    public function __construct(private PenyimpanGambar $gambar) {}

    /**
     * Banyak foto sekaligus. Data (judul, keterangan, tanggal, berita_id) dipakai untuk semua foto.
     *
     * @param  array<int, UploadedFile>  $berkas
     * @param  array<string, mixed>  $data
     * @return Collection<int, Foto>
     */
    public function tambah(array $berkas, array $data = []): Collection
    {
        $beritaId = $data['berita_id'] ?? null;
        $urutan = (int) Foto::query()->where('berita_id', $beritaId)->max('urutan');
        $tersimpan = collect();

        foreach (array_values($berkas) as $i => $file) {
            $tersimpan->push(Foto::query()->create([
                'berita_id' => $beritaId,
                'judul' => $data['judul'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'tanggal' => $data['tanggal'] ?? null,
                'urutan' => $urutan + $i + 1,
                'gambar' => $this->gambar->simpan($file, "foto.{$i}"),
            ]));
        }

        return $tersimpan;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ubah(Foto $foto, array $data, ?UploadedFile $gantiFoto): void
    {
        $lama = $foto->gambar;
        $baru = $gantiFoto ? $this->gambar->simpan($gantiFoto, 'foto') : $lama;

        $foto->update($data + ['gambar' => $baru]);

        if ($lama !== $baru) {
            $this->gambar->hapus($lama);
        }
    }

    public function hapus(Foto $foto): void
    {
        $gambar = $foto->gambar;

        $foto->delete();
        $this->gambar->hapus($gambar);
    }
}
