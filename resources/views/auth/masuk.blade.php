@extends('auth.kerangka')
@section('judul', 'Masuk admin')

@section('isi')
    <h1 class="text-2xl font-extrabold text-navy">Masuk admin</h1>
    <p class="mt-1 text-redup">Khusus pengelola desa. Warga tidak perlu masuk untuk melihat status peringatan dini.</p>

    <div class="mt-5">
        <x-pesan />
    </div>

    <form method="POST" action="{{ route('masuk.proses') }}" class="space-y-4">
        @csrf
        <x-bidang nama="email" label="Email pengelola" type="email" autocomplete="username" required autofocus />
        <div>
            <x-bidang nama="password" label="Kata sandi" type="password" value="" autocomplete="current-password" required />
            <p class="mt-1 text-right">
                <a href="{{ route('password.request') }}" class="inline-flex min-h-11 items-center rounded-lg px-1 font-bold text-biru hover:underline">Lupa kata sandi?</a>
            </p>
        </div>
        <x-tombol class="w-full py-3 text-base">Masuk ke dasbor admin <x-ikon nama="panah" class="size-5" /></x-tombol>
    </form>
@endsection
