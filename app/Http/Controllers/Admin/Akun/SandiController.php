<?php

namespace App\Http\Controllers\Admin\Akun;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GantiSandiRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SandiController extends Controller
{
    public function edit(): View
    {
        return view('admin.akun.sandi');
    }

    public function update(GantiSandiRequest $request): RedirectResponse
    {
        // remember_token diganti agar sesi "ingat saya" di perangkat lain ikut tidak berlaku.
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('admin.akun.sandi.edit')
            ->with('sukses', 'Kata sandi sudah diganti. Pakai kata sandi baru saat masuk berikutnya.');
    }
}
