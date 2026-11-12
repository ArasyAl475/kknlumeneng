<?php

namespace App\Http\Controllers\Admin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\FotoProdukRequest;
use App\Http\Requests\Toko\ProdukRequest;
use App\Models\Toko\Aktivitas;
use App\Models\Toko\Produk;
use App\Services\Toko\DaftarMarketplace;
use App\Services\Toko\PenyimpanProduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProdukController extends Controller
{
    public function index(): View
    {
        return view('admin.toko.produk.index', [
            'produk' => Produk::query()->with(['foto', 'tautan', 'varian'])->orderBy('urutan')->orderBy('id')->get(),
            'terhapus' => Produk::onlyTrashed()->orderByDesc('deleted_at')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.toko.produk.form', $this->dataForm(new Produk(['tersedia' => true, 'tampil' => true]), $request));
    }

    public function store(ProdukRequest $request): RedirectResponse
    {
        $produk = PenyimpanProduk::simpan(new Produk, $request->dataProduk());
        Aktivitas::catat($request->user(), 'tambah_produk', "Menambah produk {$produk->nama}.");

        // Foto hanya bisa diunggah setelah produk punya ID, jadi admin diarahkan ke halaman ubah.
        $tujuan = route('admin.toko.produk.edit', ['produk' => $produk, 'kembali' => $request->validated('kembali')]);

        return redirect()->to($tujuan.'#foto')
            ->with('sukses', "Produk {$produk->nama} ditambahkan. Sekarang tambahkan fotonya di bawah.");
    }

    public function edit(Request $request, Produk $produk): View
    {
        return view('admin.toko.produk.form', $this->dataForm($produk->load(['foto', 'tautan', 'varian']), $request));
    }

    public function update(ProdukRequest $request, Produk $produk): RedirectResponse
    {
        $namaLama = $produk->nama;
        PenyimpanProduk::simpan($produk, $request->dataProduk());
        Aktivitas::catat($request->user(), 'ubah_produk', $namaLama === $produk->nama
            ? "Mengubah produk {$produk->nama}."
            : "Mengubah produk {$namaLama} menjadi {$produk->nama}.");

        $kembali = $request->validated('kembali');

        return ($kembali ? to_route('admin.toko.marketplace.show', $kembali) : to_route('admin.toko.produk.index'))
            ->with('sukses', "Produk {$produk->nama} disimpan.");
    }

    public function destroy(Request $request, Produk $produk): RedirectResponse
    {
        $produk->delete();
        Aktivitas::catat($request->user(), 'hapus_produk', "Menghapus produk {$produk->nama}.");

        return back()->with('sukses', "Produk {$produk->nama} dihapus dari situs. Bisa dipulihkan dari bagian bawah halaman ini.");
    }

    public function pulihkan(Request $request, int $id): RedirectResponse
    {
        $produk = Produk::onlyTrashed()->findOrFail($id);
        $produk->restore();
        Aktivitas::catat($request->user(), 'pulihkan_produk', "Memulihkan produk {$produk->nama}.");

        return back()->with('sukses', "Produk {$produk->nama} dipulihkan.");
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(Produk $produk, Request $request): array
    {
        $kembali = $request->query('kembali');

        return [
            'produk' => $produk,
            'spesifikasiUtama' => $produk->spesifikasiUtama(),
            'kembali' => is_string($kembali) && DaftarMarketplace::ada($kembali) ? $kembali : null,
            'batasFoto' => FotoProdukRequest::batasTeks(),
            'jenisFoto' => FotoProdukRequest::jenisTeks(),
            'acceptFoto' => FotoProdukRequest::accept(),
        ];
    }
}
