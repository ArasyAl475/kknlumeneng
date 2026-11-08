@extends('layouts.admin')
@section('judul', 'Log aktivitas Toko')

@section('isi')
    <x-judul-halaman judul="Log aktivitas Toko" keterangan="Siapa menambah, mengubah, atau menghapus produk dan pengaturan toko, dan kapan." />
    <x-kartu>
        @if ($aktivitas->isEmpty())
            <p class="text-redup">Belum ada aktivitas. Perubahan produk dan pengaturan toko akan tercatat di sini.</p>
        @endif
        <ul class="divide-y divide-garis-halus">
            @foreach ($aktivitas as $a)
                <li class="py-3 first:pt-0">
                    <p>{{ $a->keterangan }}</p>
                    <p class="mt-1 text-sm text-redup">{{ $a->nama_admin }} · {{ $a->created_at->format('d-m-Y H:i') }} WIB</p>
                </li>
            @endforeach
        </ul>
        <div class="mt-3">{{ $aktivitas->links('admin.toko.halaman') }}</div>
    </x-kartu>
@endsection
