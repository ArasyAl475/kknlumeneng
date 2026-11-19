@extends('auth.kerangka')
@section('judul', 'Lupa kata sandi')

@section('isi')
    <h1 class="text-2xl font-extrabold text-navy">Lupa kata sandi</h1>
    <p class="mt-1 text-redup">Tulis email akun admin. Kami kirim tautan untuk membuat kata sandi baru. Tautan berlaku {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} menit.</p>

    <div class="mt-5">
        <x-pesan />
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-bidang nama="email" label="Email akun admin" type="email" autocomplete="username" required autofocus />
        <x-tombol class="w-full py-3 text-base">Kirim tautan ke email <x-ikon nama="panah" class="size-5" /></x-tombol>
    </form>
@endsection

@section('tautan-bawah')
    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 font-bold text-white hover:underline focus-visible:outline-white">
        <x-ikon nama="panah" class="size-4 rotate-180" /> Kembali ke halaman masuk
    </a>
@endsection
