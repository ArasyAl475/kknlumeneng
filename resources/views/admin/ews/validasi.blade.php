@extends('layouts.admin')
@section('judul', 'Validasi dan Awas')
@push('skrip')
    @vite('resources/js/ews/admin-umum.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Validasi dan Awas" keterangan="Status Awas hanya muncul dari kabel putus, persetujuan Anda, atau pernyataan Anda di halaman ini." />

    @php $takDiketahui = $bisaDinyatakan->where('kondisi_tak_diketahui', true); @endphp
    @if ($takDiketahui->isNotEmpty())
        <div role="alert" data-warna="merah" class="status-lembut status-garis mb-4 flex items-start gap-2 rounded-md border p-3 font-bold">
            <x-ikon nama="offline" class="mt-0.5 size-5 shrink-0" />
            <span>
                Alat berhenti mengirim data saat kondisi Waspada: {{ $takDiketahui->pluck('nama')->implode(', ') }}.
                Kondisi lereng tidak diketahui. Periksa lapangan lewat kader, lalu putuskan apakah perlu dinyatakan Awas (di bagian paling bawah halaman ini).
            </span>
        </div>
    @endif

    <x-kartu judul="Antrean validasi">
        @forelse ($laporan as $l)
            <article class="border-t border-garis-halus py-4 first:border-t-0 first:pt-0">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                    <h3 class="text-lg font-bold">{{ $l->wilayah->nama }}</h3>
                    <p class="text-sm text-redup">{{ $l->created_at->format('d-m-Y H:i') }} WIB</p>
                </div>
                <p class="mt-1">Pemicu: {{ $l->pemicu }}</p>
                <dl class="mt-3 grid grid-cols-1 gap-2 min-[420px]:grid-cols-2 lg:grid-cols-4">
                    @foreach (\App\Services\Ews\Sensor::DAFTAR as $kunci => $s)
                        @php $level = $l->level_sensor[$kunci] ?? null; @endphp
                        <div @class(['rounded border border-garis-halus p-2', 'bg-waspada-lembut' => $level >= 1])>
                            <dt class="text-sm text-redup">{{ $s['nama'] }}</dt>
                            <dd class="font-bold">{{ \App\Services\Ews\Sensor::teks($kunci, $l->{$kunci}) }}@if ($level === 2) <span class="text-sm">· lewat batas</span>@endif</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="mt-3 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.ews.validasi.setujui', $l) }}" data-konfirmasi="Setujui laporan ini? {{ $l->wilayah->nama }} akan berstatus AWAS.">
                        @csrf
                        <x-tombol varian="bahaya">Setujui, jadikan Awas</x-tombol>
                    </form>
                    <form method="POST" action="{{ route('admin.ews.validasi.tolak', $l) }}">
                        @csrf
                        <x-tombol varian="kedua">Tolak</x-tombol>
                    </form>
                </div>
            </article>
        @empty
            <p class="text-redup">Tidak ada laporan yang menunggu. Laporan baru muncul saat sensor melewati batas validasi.</p>
        @endforelse
    </x-kartu>

    <x-kartu judul="Wilayah berstatus Awas" class="mt-4">
        @forelse ($wilayahAwas as $w)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-garis-halus py-3 first:border-t-0 first:pt-0">
                <div>
                    <p class="flex flex-wrap items-center gap-2 font-bold"><x-lencana status="Awas" />{{ $w->nama }}</p>
                    <p class="mt-1 text-sm text-redup">{{ $w->awas_sumber }}, sejak {{ $w->awas_sejak?->format('d-m-Y H:i') }} WIB</p>
                </div>
                <x-tombol varian="kedua" :href="route('admin.ews.awas.konfirmasi', $w)">Cabut status Awas</x-tombol>
            </div>
        @empty
            <p class="text-redup">Tidak ada wilayah yang berstatus Awas.</p>
        @endforelse
    </x-kartu>

    <x-kartu judul="Nyatakan Awas sendiri" class="mt-4">
        <p class="mb-3 text-sm text-redup">Untuk keadaan yang tidak tertangkap sensor, mis. alat mati saat Waspada atau kader melihat retakan di lereng. Warga langsung melihat status Awas di dashboard.</p>
        @forelse ($bisaDinyatakan as $w)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-garis-halus py-3 first:border-t-0 first:pt-0">
                <div>
                    <p class="flex flex-wrap items-center gap-2 font-bold">
                        <x-lencana :status="$w['status']" />{{ $w['nama'] }}
                        @if ($w['kondisi_tak_diketahui'])
                            <span data-warna="merah" class="status-pekat inline-flex items-center gap-1.5 rounded px-2 py-0.5 text-sm font-bold"><x-ikon nama="offline" class="size-4 shrink-0" />Alat berhenti saat Waspada</span>
                        @elseif ($w['offline'])
                            <span class="text-sm font-normal text-redup">Alat tidak mengirim data</span>
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-redup">{{ $w['waktu'] ? 'Data terakhir '.$w['waktu'].' WIB' : 'Belum ada data' }}</p>
                </div>
                <x-tombol varian="bahaya" :href="route('admin.ews.awas.form-nyatakan', $w['id'])">Nyatakan Awas</x-tombol>
            </div>
        @empty
            <p class="text-redup">Semua wilayah sudah berstatus Awas.</p>
        @endforelse
    </x-kartu>
@endsection
