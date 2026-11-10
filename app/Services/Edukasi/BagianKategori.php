<?php

namespace App\Services\Edukasi;

use App\Models\Edukasi\Materi;
use Illuminate\Support\Collection;

/**
 * Menentukan letak sebuah materi di antara bagian-bagian lain dalam kategori yang sama,
 * supaya halaman materi bisa menampilkan "Bagian 2 dari 5" beserta tombol sebelum dan sesudahnya.
 * Hanya materi yang sudah terbit yang dihitung, karena warga tidak boleh diarahkan ke draf.
 */
class BagianKategori
{
    /**
     * @return array{daftar: Collection<int, Materi>, posisi: int|null, total: int, sebelumnya: Materi|null, berikutnya: Materi|null}
     */
    public static function untuk(Materi $materi): array
    {
        $daftar = Materi::query()
            ->terbit()
            ->where('kategori_id', $materi->kategori_id)
            ->urutBagian()
            ->get(['id', 'kategori_id', 'judul', 'slug', 'urutan'])
            ->values();

        $indeks = $daftar->search(fn (Materi $m) => $m->id === $materi->id);

        // Draf yang sedang dipratinjau tidak ada di daftar terbit, jadi tidak punya nomor bagian.
        if ($indeks === false) {
            return ['daftar' => $daftar, 'posisi' => null, 'total' => $daftar->count(), 'sebelumnya' => null, 'berikutnya' => null];
        }

        return [
            'daftar' => $daftar,
            'posisi' => $indeks + 1,
            'total' => $daftar->count(),
            'sebelumnya' => $indeks > 0 ? $daftar[$indeks - 1] : null,
            'berikutnya' => $daftar->get($indeks + 1),
        ];
    }
}
