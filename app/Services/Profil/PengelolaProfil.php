<?php

namespace App\Services\Profil;

use App\Models\Profil\Pengaturan;
use App\Models\Profil\Perangkat;
use Illuminate\Http\UploadedFile;

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
}
