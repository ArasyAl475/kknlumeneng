<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AturUlangSandiRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AturUlangSandiController extends Controller
{
    public function tampil(Request $request, string $token): View
    {
        return view('auth.atur-ulang-sandi', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function simpan(AturUlangSandiRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
            function (User $user, string $sandi): void {
                // remember_token diganti agar sesi "ingat saya" di perangkat lain ikut tidak berlaku.
                $user->forceFill(['password' => $sandi, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            // Token salah, kedaluwarsa, sudah dipakai, atau email tidak cocok: satu pesan yang sama.
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Tautan sudah kedaluwarsa, sudah dipakai, atau tidak cocok dengan email ini. Minta tautan baru lewat "Lupa kata sandi?".',
            ]);
        }

        return redirect()->route('login')->with('sukses', 'Kata sandi baru tersimpan. Silakan masuk.');
    }
}
