@extends('auth.kerangka')
@section('judul', 'Buat kata sandi baru')

@section('isi')
    <h1 class="text-2xl font-extrabold text-navy">Buat kata sandi baru</h1>
    <p class="mt-1 text-redup">Minimal 12 karakter, berisi huruf dan angka. Hindari kata umum seperti "admin" atau "lumeneng".</p>

    <div class="mt-5">
        <x-pesan />
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-bidang nama="email" label="Email akun admin" type="email" autocomplete="username" required :value="old('email', $email)" />
        <x-bidang nama="password" label="Kata sandi baru" type="password" value="" autocomplete="new-password" required autofocus minlength="12" maxlength="72" />
        <x-bidang nama="password_confirmation" label="Ulangi kata sandi baru" type="password" value="" autocomplete="new-password" required maxlength="72" />
        <x-tombol class="w-full py-3 text-base">Simpan kata sandi baru <x-ikon nama="panah" class="size-5" /></x-tombol>
    </form>
@endsection

@section('tautan-bawah')
    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 font-bold text-white hover:underline focus-visible:outline-white">
        <x-ikon nama="panah" class="size-4 rotate-180" /> Kembali ke halaman masuk
    </a>
@endsection
