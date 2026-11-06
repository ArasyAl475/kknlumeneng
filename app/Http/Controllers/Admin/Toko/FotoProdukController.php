<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\FotoProdukRequest;
use App\Models\Toko\Aktivitas;
use App\Models\Toko\FotoProduk;
use App\Models\Toko\Produk;
use App\Services\Toko\PenyimpanFoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FotoProdukController extends Controller
{
    public function store(FotoProdukRequest $request, Produk $produk): RedirectResponse
    {
        $urutan = (int) $produk->foto()->max('urutan');
        $tersimpan = 0;

        try {
            foreach ($request->file('foto') as $berkas) {
                PenyimpanFoto::simpan($produk, $berkas, $produk->foto()->exists() ? ++$urutan : 0);
                $tersimpan++;
            }
        } catch (RuntimeException) {
            return back()->with('galat', "Salah satu foto tidak bisa dibaca. {$tersimpan} foto berhasil disimpan; coba foto lain.");
        }

        Aktivitas::catat($request->user(), 'tambah_foto', "Menambah {$tersimpan} foto untuk {$produk->nama}.");

        return back()->with('sukses', "{$tersimpan} foto ditambahkan ke {$produk->nama}.");
    }

    public function destroy(Request $request, Produk $produk, FotoProduk $foto): RedirectResponse
    {
        abort_unless($foto->produk_id === $produk->id, 404);
        PenyimpanFoto::hapus($foto);
        Aktivitas::catat($request->user(), 'hapus_foto', "Menghapus satu foto dari {$produk->nama}.");

        return back()->with('sukses', 'Foto dihapus.');
    }

    // Foto pertama menjadi foto utama di daftar produk dan saat tautan dibagikan.
    public function geser(Produk $produk, FotoProduk $foto, string $arah): RedirectResponse
    {
        abort_unless($foto->produk_id === $produk->id, 404);
        $daftar = $produk->foto()->get()->values();
        $posisi = $daftar->search(fn (FotoProduk $f) => $f->is($foto));
        $tujuan = $arah === 'naik' ? $posisi - 1 : $posisi + 1;

        if ($posisi !== false && isset($daftar[$tujuan])) {
            $urut = $daftar->all();
            [$urut[$posisi], $urut[$tujuan]] = [$urut[$tujuan], $urut[$posisi]];

            DB::transaction(function () use ($urut) {
                foreach ($urut as $i => $f) {
                    $f->update(['urutan' => $i]);
                }
            });
        }

        return back()->with('sukses', 'Urutan foto diperbarui.');
    }
}
