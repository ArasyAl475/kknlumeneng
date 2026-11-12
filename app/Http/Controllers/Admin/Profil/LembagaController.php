<?php

namespace App\Http\Controllers\Admin\Profil;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\LembagaRequest;
use App\Models\Profil\Lembaga;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LembagaController extends Controller
{
    public function index(): View
    {
        return view('admin.profil.lembaga.index', ['lembaga' => Lembaga::query()->urut()->get()]);
    }

    public function create(): View
    {
        return view('admin.profil.lembaga.form', ['lembaga' => new Lembaga]);
    }

    public function store(LembagaRequest $request): RedirectResponse
    {
        $lembaga = Lembaga::query()->create($request->dataLembaga());

        return to_route('admin.profil.lembaga.index')->with('sukses', "Lembaga {$lembaga->nama} ditambahkan.");
    }

    public function edit(Lembaga $lembaga): View
    {
        return view('admin.profil.lembaga.form', ['lembaga' => $lembaga]);
    }

    public function update(LembagaRequest $request, Lembaga $lembaga): RedirectResponse
    {
        $lembaga->update($request->dataLembaga());

        return to_route('admin.profil.lembaga.index')->with('sukses', "Lembaga {$lembaga->nama} diperbarui.");
    }

    public function destroy(Lembaga $lembaga): RedirectResponse
    {
        $lembaga->delete();

        return to_route('admin.profil.lembaga.index')->with('sukses', "Lembaga {$lembaga->nama} dihapus.");
    }
}
