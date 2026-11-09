<?php

namespace App\Http\Controllers\Admin\Profil;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\PerangkatRequest;
use App\Models\Profil\Perangkat;
use App\Services\Profil\PengelolaProfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PerangkatController extends Controller
{
    public function __construct(private PengelolaProfil $pengelola) {}

    public function index(): View
    {
        return view('admin.profil.perangkat.index', [
            'perangkat' => Perangkat::query()->urut()->get()->groupBy('kelompok'),
        ]);
    }

    public function create(): View
    {
        return view('admin.profil.perangkat.form', ['perangkat' => new Perangkat(['kelompok' => 'kaur'])]);
    }

    public function store(PerangkatRequest $request): RedirectResponse
    {
        $perangkat = $this->pengelola->simpanPerangkat(new Perangkat, $request->dataPerangkat(), $request->file('foto'), false);

        return to_route('admin.profil.perangkat.index')->with('sukses', "{$perangkat->nama} ditambahkan ke struktur pemerintahan.");
    }

    public function edit(Perangkat $perangkat): View
    {
        return view('admin.profil.perangkat.form', ['perangkat' => $perangkat]);
    }

    public function update(PerangkatRequest $request, Perangkat $perangkat): RedirectResponse
    {
        $this->pengelola->simpanPerangkat($perangkat, $request->dataPerangkat(), $request->file('foto'), $request->boolean('hapus_foto'));

        return to_route('admin.profil.perangkat.index')->with('sukses', "Data {$perangkat->nama} diperbarui.");
    }

    public function destroy(Perangkat $perangkat): RedirectResponse
    {
        $this->pengelola->hapusPerangkat($perangkat);

        return to_route('admin.profil.perangkat.index')->with('sukses', "{$perangkat->nama} dihapus dari struktur pemerintahan.");
    }
}
