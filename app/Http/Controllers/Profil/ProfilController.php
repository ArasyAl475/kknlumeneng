<?php

namespace App\Http\Controllers\Profil;

use App\Http\Controllers\Controller;
use App\Models\Profil\Lembaga;
use App\Models\Profil\Perangkat;
use App\Services\Profil\ProfilDesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfilController extends Controller
{
    /**
     * Satu halaman Profil Desa berisi tiga bagian: tentang desa, visi & misi, dan pemerintahan.
     * Digabung agar warga cukup menggulir, tidak berpindah-pindah halaman lewat sinyal lemah.
     */
    public function index(): View
    {
        return view('profil.index', [
            'profil' => ProfilDesa::muat(),
            // Satu kueri untuk semua perangkat, lalu dikelompokkan sesuai tingkat bagan.
            'perangkat' => Perangkat::query()->urut()->get()->groupBy('kelompok'),
            'lembaga' => Lembaga::query()->urut()->get(),
        ]);
    }

    // Alamat lama tetap hidup (mungkin sudah dibagikan warga), diarahkan ke bagiannya di halaman gabungan.
    public function visiMisi(): RedirectResponse
    {
        return redirect()->to(route('profil.index').'#visi-misi', 301);
    }

    public function pemerintahan(): RedirectResponse
    {
        return redirect()->to(route('profil.index').'#pemerintahan', 301);
    }
}
