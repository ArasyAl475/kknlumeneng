@extends('layouts.app')
@section('judul', 'Status Peringatan Dini Longsor')
@section('deskripsi', 'Status peringatan dini longsor Desa Lumeneng: kelembapan tanah, intensitas hujan, kemiringan, peta, dan grafik. Alat bantu warga, bukan peringatan resmi BPBD.')

@push('skrip')
    @vite('resources/js/ews/dasbor.js')
@endpush

@php
    $utama = $data['utama'];
    // Kalimat yang sama dengan yang ditulis dasbor.js saat pembaruan berkala.
    $teksOffline = collect($data['offline'])
        ->map(fn (array $alat) => "Alat tidak mengirim data: {$alat['nama']} (terakhir {$alat['waktu']} WIB).")
        ->implode(' ') ?: null;
@endphp

@section('hero')
    {{-- Pita judul sengaja ringkas: status longsor di bawahnya harus segera terlihat di HP. --}}
    <x-pita-judul judul="Status Longsor Desa Lumeneng" gambar="ews.hero" label="Sistem Peringatan Dini" ikon="waspada" :ringkas="true" :jejak="[['Peringatan Dini', null]]">
        <p id="koneksi" class="kaca-terang flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-bold text-white" role="status">
            <span class="inline-block size-2 rounded-full bg-white"></span>
            Memuat pembaruan otomatis...
        </p>
    </x-pita-judul>
@endsection

@section('isi')
<div id="dasbor" data-url-data="{{ route('ews.data') }}" data-url-grafik="{{ route('ews.grafik') }}">
    <script type="application/json" id="data-awal">@json($data)</script>

    {{-- ══════════════════════════════════════
         PANEL STATUS UTAMA
    ══════════════════════════════════════ --}}
    {{-- Komponen yang sama dengan kartu status di beranda. Elemen ber-id panel-* diperbarui oleh dasbor.js. --}}
    <x-kartu-status
        :langsung="true"
        tingkat="h2"
        judul-id="panel-judul"
        label="Status desa saat ini"
        :warna="$utama['warna']"
        :status="$utama['status']"
        :saran="$utama['saran']"
        :lokasi="$utama['lokasi']"
        :pemicu="$utama['pemicu']"
        :waktu="$utama['waktu']"
        :jumlah-wilayah="count($data['wilayah'])"
        :jumlah-offline="count($data['offline'])"
        :offline="$teksOffline"
        detail="#sensor"
        :bagikan="route('ews.index')" />

    {{-- SR live region --}}
    <p id="pengumuman" class="sr-only" aria-live="polite"></p>

    {{-- Disclaimer --}}
    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-waspada-lembut px-4 py-2.5 text-sm">
        <p class="flex items-start gap-2 font-bold text-waspada-teks"><x-ikon nama="waspada" class="mt-0.5 size-4 shrink-0" />{{ __('ews.disclaimer') }}</p>
    </div>

    <noscript>
        <p class="mt-2 rounded-xl bg-awas-lembut p-4 font-bold text-awas">
            Pembaruan otomatis, peta, dan grafik butuh JavaScript. Muat ulang halaman untuk melihat status terbaru.
        </p>
    </noscript>

    {{-- ══════════════════════════════════════
         PILIH WILAYAH
    ══════════════════════════════════════ --}}
    <section class="mt-8" aria-labelledby="judul-wilayah">
        <div class="mb-3 flex items-center gap-3">
            <span class="h-6 w-1.5 rounded-full bg-aksen" aria-hidden="true"></span>
            <h2 id="judul-wilayah" class="text-xl font-extrabold text-navy">Pilih wilayah</h2>
        </div>

        <div id="daftar-wilayah"
             class="grid min-h-[calc(var(--n1)*5.5rem)] content-start gap-3 sm:min-h-[calc(var(--n2)*5.5rem)] sm:grid-cols-2 lg:min-h-[calc(var(--n3)*5.5rem)] lg:grid-cols-3"
             style="--n1: {{ count($data['wilayah']) }}; --n2: {{ ceil(count($data['wilayah']) / 2) }}; --n3: {{ ceil(count($data['wilayah']) / 3) }}">
        </div>

        @if (! $data['wilayah'])
            <div class="flex flex-col items-center rounded-2xl border-2 border-dashed border-garis-halus py-10 text-center">
                <x-ikon nama="kosong" class="size-10 text-redup" />
                <p class="mt-3 font-bold">Belum ada wilayah yang dipantau.</p>
                <p class="mt-1 text-sm text-redup">Pengelola perlu menambahkannya di halaman admin.</p>
            </div>
        @endif
    </section>

    {{-- ══════════════════════════════════════
         BACAAN SENSOR
    ══════════════════════════════════════ --}}
    {{-- id="sensor": tujuan tombol "Detail sensor lingkungan" di kartu status (juga dari beranda). --}}
    <section id="sensor" class="mt-8" aria-labelledby="judul-sensor">
        <div class="mb-3 flex flex-wrap items-baseline justify-between gap-x-4">
            <div class="flex items-center gap-3">
                <span class="h-6 w-1.5 rounded-full bg-aksen" aria-hidden="true"></span>
                <h2 id="judul-sensor" class="text-xl font-extrabold text-navy">
                    Bacaan sensor <span id="sensor-wilayah"></span>
                </h2>
            </div>
            <p id="sensor-waktu" class="text-sm text-redup"></p>
        </div>

        <div id="kartu-sensor"
             @class(['grid grid-cols-1 content-start gap-x-4 gap-y-3 min-[420px]:grid-cols-2 lg:grid-cols-4',
                     'min-h-[54rem] min-[420px]:min-h-[27rem] lg:min-h-[13rem]' => $data['wilayah']])>
        </div>
    </section>

    {{-- ══════════════════════════════════════
         PETA WILAYAH
    ══════════════════════════════════════ --}}
    <section class="mt-8" aria-labelledby="judul-peta">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="h-6 w-1.5 rounded-full bg-aksen" aria-hidden="true"></span>
                <h2 id="judul-peta" class="text-xl font-extrabold text-navy">Peta wilayah</h2>
            </div>
            <div>
                <label for="mode-peta" class="mb-1 block text-sm font-bold">Jenis peta</label>
                <select id="mode-peta" class="isian min-w-[160px]">
                    <option value="standar">Standar</option>
                    <option value="satelit">Satelit</option>
                    <option value="topografi">Ketinggian</option>
                </select>
            </div>
        </div>

        <div id="peta"
             class="relative z-0 h-80 overflow-hidden rounded-[0.9rem] border border-garis-halus bg-kosong-lembut shadow-kartu lg:h-[28rem]"
             role="region"
             aria-label="Peta wilayah. Daftar wilayah di atas berisi informasi yang sama.">
        </div>
        <p id="peta-galat" class="mt-2 hidden rounded-xl bg-awas-lembut p-3 font-bold text-awas">
            Peta gagal dimuat. Status tiap wilayah tetap bisa dilihat di daftar di atas.
        </p>
        <p class="mt-2 flex items-start gap-2 text-sm text-redup">
            <x-ikon nama="info" class="mt-0.5 size-4 shrink-0 text-biru" />Ketuk wilayah untuk melihat bacaannya. Di HP, geser peta dengan dua jari. Garis: tipis = Aman, putus-putus = Waspada, tebal = Awas.
        </p>
    </section>

    {{-- ══════════════════════════════════════
         GRAFIK RIWAYAT
    ══════════════════════════════════════ --}}
    <section class="mt-8" aria-labelledby="judul-grafik">
        <div class="mb-3 flex items-center gap-3">
            <span class="h-6 w-1.5 rounded-full bg-aksen" aria-hidden="true"></span>
            <h2 id="judul-grafik" class="text-xl font-extrabold text-navy">
                Riwayat sensor <span id="grafik-wilayah"></span>
            </h2>
        </div>

        <div class="flex flex-wrap gap-x-4 gap-y-3">
            {{-- Pilih sensor --}}
            <div role="group" aria-label="Pilih sensor" class="flex flex-wrap gap-2" id="pilih-sensor">
                @foreach ($sensor as $kunci => $s)
                    <button type="button"
                            data-sensor="{{ $kunci }}"
                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                            class="inline-flex min-h-11 items-center gap-2 rounded-xl border-2 border-garis bg-permukaan px-4 font-bold transition-all hover:border-kopi hover:bg-kopi/5 aria-pressed:border-kopi aria-pressed:bg-kopi aria-pressed:text-white">
                        <x-ikon :nama="$s['ikon']" class="size-4" />{{ $s['nama'] }}
                    </button>
                @endforeach
            </div>

            {{-- Pilih rentang --}}
            <div role="group" aria-label="Pilih rentang waktu" class="flex flex-wrap gap-2" id="pilih-rentang">
                @foreach (['1j' => '1 jam', '24j' => '24 jam', '7h' => '7 hari'] as $kunci => $label)
                    <button type="button"
                            data-rentang="{{ $kunci }}"
                            aria-pressed="{{ $kunci === '24j' ? 'true' : 'false' }}"
                            class="inline-flex min-h-11 items-center rounded-xl border-2 border-garis bg-permukaan px-4 font-bold transition-all hover:border-kopi hover:bg-kopi/5 aria-pressed:border-kopi aria-pressed:bg-kopi aria-pressed:text-white">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="relative mt-3 h-72 overflow-hidden rounded-[0.9rem] border border-garis-halus bg-permukaan p-3 shadow-kartu">
            <canvas id="grafik" role="img" aria-label="Grafik riwayat sensor"></canvas>
            <p id="grafik-pesan" class="absolute inset-0 hidden place-content-center p-4 text-center font-bold text-redup"></p>
        </div>
        <p id="grafik-ringkas" class="mt-2 text-sm text-redup"></p>
    </section>

    {{-- ══════════════════════════════════════
         LOG KEJADIAN
    ══════════════════════════════════════ --}}
    <section class="mt-8" aria-labelledby="judul-log">
        <div class="mb-3 flex items-center gap-3">
            <span class="h-6 w-1.5 rounded-full bg-aksen" aria-hidden="true"></span>
            <h2 id="judul-log" class="text-xl font-extrabold text-navy">Kejadian terbaru</h2>
        </div>

        <div class="overflow-hidden rounded-[0.9rem] border border-garis-halus bg-permukaan shadow-kartu">
            <ul id="log" class="divide-y divide-garis-halus" data-paginasi="10"></ul>
            <p id="log-kosong" class="hidden px-5 py-8 text-center text-redup">
                Belum ada perubahan status yang tercatat.
            </p>
        </div>
    </section>

    {{-- ══════════════════════════════════════
         TEMPLATE JAVASCRIPT
    ══════════════════════════════════════ --}}
    <template id="cetak-wilayah">
        <button type="button" aria-pressed="false"
                class="flex min-h-14 w-full items-center justify-between gap-3 rounded-2xl border-2 border-garis bg-permukaan p-4 text-left transition-all hover:border-kopi hover:shadow-sm aria-pressed:border-kopi aria-pressed:shadow-md aria-pressed:ring-2 aria-pressed:ring-kopi/30">
            <span class="min-w-0">
                <span data-isi="nama" class="block font-bold"></span>
                <span data-isi="info" class="block text-sm text-redup"></span>
            </span>
            <span data-isi="lencana"></span>
        </button>
    </template>

    <template id="cetak-lencana">
        <span class="status-pekat inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-sm font-bold whitespace-nowrap">
            <svg class="size-4 shrink-0" aria-hidden="true"><use></use></svg>
            <span data-isi="kata"></span>
        </span>
    </template>

    {{-- Kartu sensor bertumpuk ke belakang: dua lapisan di balik kartu memberi kesan kedalaman (gaya di app.css). --}}
    <template id="cetak-sensor">
        <div class="kartu-sensor">
            <span class="kartu-sensor__lapis kartu-sensor__lapis--2" aria-hidden="true"></span>
            <span class="kartu-sensor__lapis kartu-sensor__lapis--1" aria-hidden="true"></span>
            <div class="kartu-sensor__muka">
                <div class="flex items-start justify-between gap-3">
                    <span class="kartu-sensor__ikon"><svg class="size-6" aria-hidden="true"><use data-isi="ikon"></use></svg></span>
                    <span data-isi="lencana"></span>
                </div>
                <p data-isi="nama" class="mt-4 text-sm font-bold text-redup"></p>
                <p data-isi="teks" class="mt-1 font-judul text-[2.4rem] font-extrabold leading-none tabular-nums text-tinta"></p>
                <div class="kartu-sensor__meter" data-isi="meter" aria-hidden="true"><span data-isi="isi-meter"></span></div>
            </div>
        </div>
    </template>

    <template id="cetak-log">
        <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3">
            <span data-isi="lencana"></span>
            <span class="min-w-0 flex-1 basis-48">
                <span data-isi="wilayah" class="font-bold"></span>:
                <span data-isi="keterangan"></span>
            </span>
            <span data-isi="waktu" class="text-sm text-redup"></span>
        </li>
    </template>

</div>
@endsection
