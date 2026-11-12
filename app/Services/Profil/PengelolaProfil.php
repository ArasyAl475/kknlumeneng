<?php

namespace App\Services\Profil;

use App\Models\Profil\Foto;
use App\Models\Profil\Pengaturan;
use App\Models\Profil\Perangkat;
use App\Models\Profil\Potensi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PengelolaProfil
{
    public function __construct(private PenyimpanGambar $gambar) {}

    /**
     * @param  array<string, ?string>  $data  isian teks yang sudah divalidasi
     * @param  array<string, ?UploadedFile>  $fotoBaru
     * @param  array<string, bool>  $hapusFoto
     */
    public function simpanBagian(string $bagian, array $data, array $fotoBaru = [], array $hapusFoto = []): void
    {
        $sekarang = Pengaturan::semua();
        $fotoLama = [];

        foreach (BagianProfil::ambil($bagian)['foto'] as $kolom) {
            $lama = $sekarang[$kolom] ?: null;
            $baru = $this->gambar->ganti($lama, $fotoBaru[$kolom] ?? null, $hapusFoto[$kolom] ?? false, $kolom);
            $data[$kolom] = $baru ?? '';

            if ($lama !== $baru) {
                $fotoLama[] = $lama;
            }
        }

        // Teks kosong disimpan sebagai string kosong, bukan null, agar bawaan tidak muncul lagi.
        Pengaturan::simpan(array_map(fn (?string $isi) => $isi ?? '', $data));

        // Foto lama dihapus setelah data tersimpan, agar halaman tidak menunjuk berkas yang hilang.
        array_walk($fotoLama, fn (?string $jalur) => $this->gambar->hapus($jalur));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpanPerangkat(Perangkat $perangkat, array $data, ?UploadedFile $foto, bool $hapusFoto): Perangkat
    {
        $lama = $perangkat->foto;
        $baru = $this->gambar->ganti($lama, $foto, $hapusFoto, 'foto');

        $perangkat->fill($data + ['foto' => $baru])->save();

        if ($lama !== $baru) {
            $this->gambar->hapus($lama);
        }

        return $perangkat;
    }

    public function hapusPerangkat(Perangkat $perangkat): void
    {
        $foto = $perangkat->foto;

        $perangkat->delete();
        $this->gambar->hapus($foto);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpanPotensi(Potensi $potensi, array $data, array $fotoBaru = [], array $hapusId = [], ?int $utamaId = null): Potensi
    {
        $namaLama = $potensi->exists ? $potensi->nama : null;
        $berkasDihapus = [];

        DB::transaction(function () use ($potensi, $data, $fotoBaru, $hapusId, $utamaId, $namaLama, &$berkasDihapus) {
            $potensi->fill($data)->save();

            // Foto yang judulnya masih nama lama ikut berganti nama; judul yang diubah admin di menu Galeri dibiarkan.
            if ($namaLama !== null && $namaLama !== $potensi->nama) {
                $potensi->fotos()->where('judul', $namaLama)->update(['judul' => $potensi->nama]);
            }

            // Hanya foto milik potensi ini yang bisa dihapus, walau id lain dikirim.
            foreach ($potensi->fotos()->whereIn('id', $hapusId)->get() as $foto) {
                $berkasDihapus[] = $foto->gambar;
                $foto->delete();
            }

            $urutan = (int) $potensi->fotos()->max('urutan');
            foreach (array_values($fotoBaru) as $i => $berkas) {
                $potensi->fotos()->create([
                    'gambar' => $this->gambar->simpan($berkas, "fotos.{$i}"),
                    'judul' => $potensi->nama,
                    'keterangan' => $this->keteranganFoto($potensi),
                    'urutan' => ++$urutan,
                ]);
            }

            $this->rapikanUrutanFoto($potensi, $utamaId);
        });

        foreach ($berkasDihapus as $jalur) {
            $this->gambar->hapus($jalur);
        }

        return $potensi;
    }

    public function hapusPotensi(Potensi $potensi): void
    {
        $berkas = $potensi->fotos()->pluck('gambar')->all();

        // Baris foto ikut terhapus (cascade), jadi hilang juga dari Galeri.
        $potensi->delete();

        foreach ($berkas as $jalur) {
            $this->gambar->hapus($jalur);
        }
    }

    // Satu urutan rapi 1, 2, 3, ...; foto yang dipilih sebagai utama dipindahkan ke urutan pertama.
    private function rapikanUrutanFoto(Potensi $potensi, ?int $utamaId): void
    {
        $id = $potensi->fotos()->pluck('id')->all();

        if ($utamaId !== null && in_array($utamaId, $id, true)) {
            $id = [$utamaId, ...array_values(array_diff($id, [$utamaId]))];
        }

        foreach ($id as $i => $fotoId) {
            Foto::query()->whereKey($fotoId)->where('urutan', '!=', $i + 1)->update(['urutan' => $i + 1]);
        }
    }

    private function keteranganFoto(Potensi $potensi): string
    {
        return Str::limit('Potensi desa: '.$potensi->labelKategori().($potensi->lokasi ? ' · '.$potensi->lokasi : ''), 500);
    }
}
