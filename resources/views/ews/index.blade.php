@extends('layouts.app')
@section('judul', 'Status Peringatan Dini Longsor')
@section('deskripsi', 'Status peringatan dini longsor Desa Lumeneng: kelembapan tanah, intensitas hujan, kemiringan, peta, dan grafik. Alat bantu warga, bukan peringatan resmi BPBD.')

@push('skrip')
    @vite('resources/js/ews/dasbor.js')
@endpush

@php
    $utama = $data['utama'];
    $ikonStatus = ['hijau' => 'aman', 'kuning' => 'waspada', 'merah' => 'awas', 'kosong' => 'kosong'];
@endphp

@section('isi')
<div id="dasbor" data-url-data="{{ route('ews.data') }}" data-url-grafik="{{ route('ews.grafik') }}">
    <script type="application/json" id="data-awal">@json($data)</script>

    {{-- Bagian ini sudah terisi dari server, jadi status tetap terbaca walau skrip gagal termuat. --}}
    <section id="panel" data-warna="{{ $utama['warna'] }}" class="status-pekat rounded-lg p-5 sm:p-6" aria-labelledby="panel-judul">
        <p class="font-bold">Status desa saat ini</p>
        <h1 id="panel-judul" class="mt-1 flex items-center gap-3 text-[clamp(2.25rem,9vw,3.5rem)] leading-none font-bold">
            <svg class="size-[1em] shrink-0" aria-hidden="true"><use id="panel-ikon" href="#i-{{ $ikonStatus[$utama['warna']] }}"></use></svg>
            <span id="panel-status">{{ $utama['status'] ?? 'Belum ada data' }}</span>
        </h1>
        <p id="panel-saran" class="mt-3 max-w-2xl text-lg">{{ $utama['saran'] }}</p>
        <p class="mt-3"><span id="panel-lokasi" class="font-bold">{{ $utama['lokasi'] }}</span> <span id="panel-pemicu">{{ $utama['pemicu'] }}</span></p>
        <p class="mt-1 text-sm">Data terakhir: <span id="panel-waktu">{{ $utama['waktu'] ? $utama['waktu'].' WIB' : '-' }}</span></p>
    </section>

    {{-- Hanya perubahan status yang diumumkan, bukan setiap pembaruan berkala. --}}
    <p id="pengumuman" class="sr-only" aria-live="polite"></p>

    <div id="banner-alat" role="status" data-warna="kosong" @class(['status-lembut status-garis mt-3 flex items-start gap-2 rounded-md border-2 p-3 font-bold', 'hidden' => ! $data['offline']])>
        <x-ikon nama="offline" class="mt-0.5 size-6 shrink-0" />
        <span id="banner-alat-teks">
            @foreach ($data['offline'] as $alat)
                Alat tidak mengirim data: {{ $alat['nama'] }} (terakhir {{ $alat['waktu'] }} WIB).
            @endforeach
        </span>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-sm">
        <p class="font-bold">{{ __('ews.disclaimer') }}</p>
        <p id="koneksi" class="text-redup" role="status">Memuat pembaruan otomatis...</p>
    </div>
    <noscript><p class="mt-2 font-bold">Pembaruan otomatis, peta, dan grafik butuh JavaScript. Muat ulang halaman untuk melihat status terbaru.</p></noscript>

    <section class="mt-8" aria-labelledby="judul-wilayah">
        <h2 id="judul-wilayah" class="text-xl font-bold">Pilih wilayah</h2>
        {{-- Ruang dipesan lebih dulu agar isi di bawahnya tidak melompat saat kartu digambar skrip. --}}
        <div id="daftar-wilayah" class="mt-3 grid min-h-[calc(var(--n1)*5rem)] content-start gap-2 sm:min-h-[calc(var(--n2)*5rem)] sm:grid-cols-2 lg:min-h-[calc(var(--n3)*5rem)] lg:grid-cols-3" style="--n1: {{ count($data['wilayah']) }}; --n2: {{ ceil(count($data['wilayah']) / 2) }}; --n3: {{ ceil(count($data['wilayah']) / 3) }}"></div>
        @if (! $data['wilayah'])
            <p class="mt-2 text-redup">Belum ada wilayah yang dipantau. Pengelola perlu menambahkannya di halaman admin.</p>
        @endif
    </section>

    <section class="mt-8" aria-labelledby="judul-sensor">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4">
            <h2 id="judul-sensor" class="text-xl font-bold">Bacaan sensor <span id="sensor-wilayah"></span></h2>
            <p id="sensor-waktu" class="text-sm text-redup"></p>
        </div>
        <div id="kartu-sensor" @class(['mt-3 grid grid-cols-1 content-start gap-3 min-[420px]:grid-cols-2 lg:grid-cols-4', 'min-h-[33rem] min-[420px]:min-h-[16.5rem] lg:min-h-[8rem]' => $data['wilayah']])></div>
    </section>

    <section class="mt-8" aria-labelledby="judul-peta">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h2 id="judul-peta" class="text-xl font-bold">Peta wilayah</h2>
            <div>
                <label for="mode-peta" class="block text-sm font-bold">Jenis peta</label>
                <select id="mode-peta" class="isian">
                    <option value="standar">Standar</option>
                    <option value="satelit">Satelit</option>
                    <option value="topografi">Ketinggian</option>
                </select>
            </div>
        </div>
        <div id="peta" class="relative z-0 mt-3 h-80 rounded-lg border border-garis-halus bg-kosong-lembut lg:h-[28rem]" role="region" aria-label="Peta wilayah. Daftar wilayah di atas berisi informasi yang sama."></div>
        <p id="peta-galat" class="mt-2 hidden font-bold">Peta gagal dimuat. Status tiap wilayah tetap bisa dilihat di daftar di atas.</p>
        <p class="mt-2 text-sm text-redup">Ketuk wilayah untuk melihat bacaannya. Di HP, geser peta dengan dua jari. Garis: tipis = Aman, putus-putus = Waspada, tebal = Awas.</p>
    </section>

    <section class="mt-8" aria-labelledby="judul-grafik">
        <h2 id="judul-grafik" class="text-xl font-bold">Riwayat sensor <span id="grafik-wilayah"></span></h2>
        <div class="mt-3 flex flex-wrap gap-x-6 gap-y-3">
            <div role="group" aria-label="Pilih sensor" class="flex flex-wrap gap-2" id="pilih-sensor">
                @foreach ($sensor as $kunci => $s)
                    <button type="button" data-sensor="{{ $kunci }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" class="inline-flex min-h-11 items-center gap-2 rounded-md border border-garis bg-permukaan px-3 font-bold hover:bg-kosong-lembut aria-pressed:bg-kopi aria-pressed:text-white">
                        <x-ikon :nama="$s['ikon']" />{{ $s['nama'] }}
                    </button>
                @endforeach
            </div>
            <div role="group" aria-label="Pilih rentang waktu" class="flex flex-wrap gap-2" id="pilih-rentang">
                @foreach (['1j' => '1 jam', '24j' => '24 jam', '7h' => '7 hari'] as $kunci => $label)
                    <button type="button" data-rentang="{{ $kunci }}" aria-pressed="{{ $kunci === '24j' ? 'true' : 'false' }}" class="inline-flex min-h-11 items-center rounded-md border border-garis bg-permukaan px-3 font-bold hover:bg-kosong-lembut aria-pressed:bg-kopi aria-pressed:text-white">{{ $label }}</button>
                @endforeach
            </div>
        </div>
        <div class="relative mt-3 h-72 rounded-lg border border-garis-halus bg-permukaan p-2">
            <canvas id="grafik" role="img" aria-label="Grafik riwayat sensor"></canvas>
            <p id="grafik-pesan" class="absolute inset-0 hidden place-content-center p-4 text-center font-bold text-redup"></p>
        </div>
        <p id="grafik-ringkas" class="mt-2 text-sm text-redup"></p>
    </section>

    <section class="mt-8" aria-labelledby="judul-log">
        <h2 id="judul-log" class="text-xl font-bold">Kejadian terbaru</h2>
        <ul id="log" class="mt-3 divide-y divide-garis-halus border-y border-garis-halus"></ul>
        <p id="log-kosong" class="mt-3 hidden text-redup">Belum ada perubahan status yang tercatat.</p>
    </section>

    {{-- Cetakan untuk JavaScript: teks diisi lewat textContent sehingga aman dari data pengguna. --}}
    <template id="cetak-wilayah">
        <button type="button" aria-pressed="false" class="flex min-h-11 w-full items-center justify-between gap-2 rounded-md border border-garis bg-permukaan p-3 text-left hover:bg-kosong-lembut aria-pressed:outline-3 aria-pressed:outline-tinta">
            <span class="min-w-0"><span data-isi="nama" class="block font-bold"></span><span data-isi="info" class="block text-sm text-redup"></span></span>
            <span data-isi="lencana"></span>
        </button>
    </template>
    <template id="cetak-lencana">
        <span class="status-pekat inline-flex items-center gap-1.5 rounded px-2 py-0.5 text-sm font-bold whitespace-nowrap"><svg class="size-4 shrink-0" aria-hidden="true"><use></use></svg><span data-isi="kata"></span></span>
    </template>
    <template id="cetak-sensor">
        <div class="status-garis rounded-lg border-2 bg-permukaan p-3">
            <p class="flex items-center gap-2 text-sm font-bold"><svg class="size-5 shrink-0" aria-hidden="true"><use data-isi="ikon"></use></svg><span data-isi="nama"></span></p>
            <p data-isi="teks" class="mt-1 text-2xl font-bold"></p>
            <p class="mt-1"><span data-isi="lencana"></span></p>
        </div>
    </template>
    <template id="cetak-log">
        <li class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2">
            <span data-isi="lencana"></span>
            <span class="min-w-0 flex-1 basis-48"><span data-isi="wilayah" class="font-bold"></span>: <span data-isi="keterangan"></span></span>
            <span data-isi="waktu" class="text-sm text-redup"></span>
        </li>
    </template>
</div>
@endsection
