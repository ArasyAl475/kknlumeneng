<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MasukRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MasukController extends Controller
{
    public function tampil(): View
    {
        return view('auth.masuk');
    }

    public function proses(MasukRequest $request): RedirectResponse
    {
        // Akun non-admin mendapat pesan yang sama dengan sandi salah agar penyerang
        // tidak bisa mengetahui akun mana yang berhak masuk.
        if (! Auth::attempt($request->safe()->only(['email', 'password'])) || ! $request->user()->is_admin) {
            Auth::logout();

            return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.beranda'));
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
