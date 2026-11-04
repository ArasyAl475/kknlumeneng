@extends('layouts.app')
@section('judul', 'Masuk admin')

@section('isi')
    <div class="mx-auto max-w-sm">
        <x-judul-halaman judul="Masuk admin" keterangan="Khusus pengelola. Warga tidak perlu masuk untuk melihat status peringatan dini." />
        <x-kartu>
            <form method="POST" action="{{ route('masuk.proses') }}" class="space-y-4">
                @csrf
                <x-bidang nama="email" label="Email" type="email" autocomplete="username" required autofocus />
                <x-bidang nama="password" label="Kata sandi" type="password" value="" autocomplete="current-password" required />
                <x-tombol class="w-full">Masuk</x-tombol>
            </form>
        </x-kartu>
    </div>
@endsection
