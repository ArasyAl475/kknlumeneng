<?php

namespace App\Http\Controllers\Admin\Profil;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\PengaturanRequest;
use App\Services\Profil\BagianProfil;
use App\Services\Profil\PengelolaProfil;
use App\Services\Profil\ProfilDesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    // $bagian datang dari rute (lihat routes/admin/profil.php), jadi nilainya selalu salah satu BagianProfil.
    public function edit(string $bagian): View
    {
        return view("admin.profil.pengaturan.{$bagian}", [
            'bagian' => $bagian,
            'info' => BagianProfil::ambil($bagian),
            'profil' => ProfilDesa::muat(),
        ]);
    }

    public function update(PengaturanRequest $request, PengelolaProfil $pengelola, string $bagian): RedirectResponse
    {
        $pengelola->simpanBagian($bagian, $request->dataTeks(), $request->fotoBaru(), $request->hapusFoto());

        return to_route("admin.profil.{$bagian}.edit")
            ->with('sukses', BagianProfil::ambil($bagian)['judul'].' tersimpan dan langsung tampil di situs.');
    }
}
