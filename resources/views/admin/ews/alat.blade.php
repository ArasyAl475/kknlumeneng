@extends('layouts.admin')
@section('judul', 'Status alat')

@section('isi')
    <x-judul-halaman judul="Status alat" keterangan="Alat dianggap tidak mengirim data setelah {{ $batasMenit }} menit tanpa kiriman." />

    @unless ($adaKunciApi)
        <div role="alert" data-warna="merah" class="status-lembut status-garis mb-4 flex items-start gap-2 rounded-md border p-3 font-bold">
            <x-ikon nama="awas" class="mt-0.5 size-5 shrink-0" />EWS_API_KEY di .env masih kosong: semua kiriman alat ditolak.
        </div>
    @endunless
    @unless ($adaTelegram)
        <div role="status" data-warna="kuning" class="status-lembut status-garis mb-4 flex items-start gap-2 rounded-md border p-3 font-bold">
            <x-ikon nama="waspada" class="mt-0.5 size-5 shrink-0" />Telegram belum diatur: pemberitahuan tidak akan terkirim.
        </div>
    @endunless

    <x-kartu>
        @if ($alat->isEmpty())
            <p class="text-redup">Belum ada wilayah. Tambahkan dulu di Daftar wilayah.</p>
        @endif
        <ul class="divide-y divide-garis-halus">
            @foreach ($alat as $a)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div>
                        <p class="font-bold">{{ $a['nama'] }} <span class="font-normal text-redup">(ID alat {{ $a['id'] }})</span></p>
                        <p class="text-sm text-redup">{{ $a['terakhir'] ? 'Terakhir mengirim '.$a['terakhir']->format('d-m-Y H:i').' WIB ('.$a['terakhir']->diffForHumans().')' : 'Belum pernah mengirim data' }}</p>
                    </div>
                    @php
                        [$warna, $ikon, $kata] = match (true) {
                            ! $a['terakhir'] => ['kosong', 'kosong', 'Belum terhubung'],
                            $a['offline'] => ['merah', 'offline', 'Tidak mengirim'],
                            default => ['hijau', 'aman', 'Mengirim'],
                        };
                    @endphp
                    <span data-warna="{{ $warna }}" class="status-pekat inline-flex items-center gap-1.5 rounded px-2 py-0.5 text-sm font-bold"><x-ikon :nama="$ikon" class="size-4 shrink-0" />{{ $kata }}</span>
                </li>
            @endforeach
        </ul>
    </x-kartu>
@endsection
