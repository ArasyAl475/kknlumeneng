@extends('layouts.admin')
@section('judul', 'Ganti kata sandi')

@section('isi')
    <x-judul-halaman judul="Ganti kata sandi" keterangan="Ganti kata sandi akun Anda sendiri. Kata sandi baru langsung berlaku untuk masuk berikutnya." />

    <x-kartu class="max-w-xl">
        <form method="POST" action="{{ route('admin.akun.sandi.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <x-bidang nama="sandi_lama" label="Kata sandi lama" type="password" value="" autocomplete="current-password" required />
            <x-bidang nama="password" label="Kata sandi baru" type="password" value="" autocomplete="new-password" required minlength="8" maxlength="72"
                bantuan="Minimal 8 karakter, berisi huruf dan angka. Hindari kata umum seperti admin atau lumeneng." />
            <x-bidang nama="password_confirmation" label="Ulangi kata sandi baru" type="password" value="" autocomplete="new-password" required maxlength="72" />
            <x-tombol>Simpan kata sandi baru</x-tombol>
        </form>
    </x-kartu>

    <p class="mt-4 max-w-xl text-sm text-redup">
        Lupa kata sandi saat akan masuk? Pakai tautan <strong>Lupa kata sandi?</strong> di halaman masuk; tautan pembuatan kata sandi baru dikirim ke {{ auth()->user()->email }}.
    </p>
@endsection
