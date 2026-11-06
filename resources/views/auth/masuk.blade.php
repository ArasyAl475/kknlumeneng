@extends('layouts.app')
@section('judul', 'Masuk admin')

@section('isi')
    <div class="mx-auto max-w-sm">

        {{-- Header --}}
        <div class="mb-6 text-center">
            <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-kopi text-white shadow-md">
                <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/>
                    <circle cx="12" cy="12" r="4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold">Masuk admin</h1>
            <p class="mt-1 text-sm text-redup">Khusus pengelola desa. Warga tidak perlu masuk untuk melihat status peringatan dini.</p>
        </div>

        {{-- Form --}}
        <div class="overflow-hidden rounded-2xl border-2 border-garis-halus bg-permukaan p-6 shadow-sm">
            <form method="POST" action="{{ route('masuk.proses') }}" class="space-y-4">
                @csrf
                <x-bidang nama="email" label="Email pengelola" type="email" autocomplete="username" required autofocus />
                <x-bidang nama="password" label="Kata sandi" type="password" value="" autocomplete="current-password" required />
                <x-tombol class="mt-2 w-full py-3 text-base">Masuk ke dasbor admin</x-tombol>
            </form>
        </div>

        <p class="mt-4 text-center text-sm text-redup">
            <a href="{{ route('landing') }}" class="tautan">← Kembali ke halaman utama</a>
        </p>
    </div>
@endsection
