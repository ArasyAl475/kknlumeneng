@extends('layouts.admin')
@section('judul', 'Beranda')

@section('isi')
    <x-judul-halaman judul="Beranda admin" keterangan="Pilih modul yang ingin dikelola dari menu di samping." />

    <x-kartu judul="Status modul">
        <ul class="divide-y divide-garis-halus">
            @foreach ($modul as $m)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $m['tersedia'] ? 'bg-aman/15 text-aman' : 'bg-kosong-lembut text-redup' }}">
                            <x-ikon :nama="$m['ikon']" class="size-5" />
                        </span>
                        <span class="font-bold">{{ $m['nama'] }}</span>
                    </div>
                    @if ($m['tersedia'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-aman/15 px-3 py-1 text-sm font-bold text-aman">
                            <span class="size-1.5 rounded-full bg-aman"></span> Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-kosong-lembut px-3 py-1 text-sm font-bold text-redup">
                            Belum aktif
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-kartu>
@endsection
