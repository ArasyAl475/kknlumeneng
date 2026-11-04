@extends('layouts.admin')
@section('judul', 'Beranda')

@section('isi')
    <x-judul-halaman judul="Beranda admin" keterangan="Pilih modul yang ingin dikelola dari menu." />
    <x-kartu judul="Modul">
        <ul class="divide-y divide-garis-halus">
            @foreach ($modul as $m)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                    <span class="font-bold">{{ $m['nama'] }}</span>
                    <span class="text-redup">{{ $m['tersedia'] ? 'Aktif' : 'Belum aktif' }}</span>
                </li>
            @endforeach
        </ul>
    </x-kartu>
@endsection
