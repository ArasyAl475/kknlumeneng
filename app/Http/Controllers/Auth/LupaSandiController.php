<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LupaSandiRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class LupaSandiController extends Controller
{
    public const PESAN_TERKIRIM = 'Jika email itu terdaftar sebagai admin, tautan untuk membuat kata sandi baru sudah dikirim. Periksa kotak masuk dan folder Spam.';

    public function tampil(): View
    {
        return view('auth.lupa-sandi');
    }

    public function kirim(LupaSandiRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        // Hanya akun admin yang dikirimi tautan: akun lain tidak punya halaman yang bisa dimasuki.
        if (User::query()->where('email', $email)->where('is_admin', true)->exists()) {
            try {
                Password::sendResetLink(['email' => $email]);
            } catch (Throwable $e) {
                // Mis. pengaturan Gmail di .env salah. Dicatat di log, tetapi pesan ke pengunjung tetap
                // sama agar orang luar tidak bisa menebak email mana yang terdaftar sebagai admin.
                report($e);
            }
        }

        return back()->with('sukses', self::PESAN_TERKIRIM);
    }
}
