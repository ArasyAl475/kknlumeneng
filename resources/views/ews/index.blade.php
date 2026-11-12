@extends('layouts.app')
@section('judul', 'Status Peringatan Dini Longsor')
@section('deskripsi', 'Status peringatan dini longsor Desa Lumeneng: kelembapan tanah, intensitas hujan, kemiringan, peta, dan grafik. Alat bantu warga, bukan peringatan resmi BPBD.')

@push('skrip')
    @vite('resources/js/ews/dasbor.js')
    {{-- Penampil layar penuh untuk gambar peta kerawanan (milik modul Profil, dipakai bersama). --}}
    @vite('resources/js/profil/galeri.js')
@endpush

@php
    $utama = $data['utama'];
    // Kalimatnya disusun server (DataDasbor), sama dengan yang dipakai dasbor.js saat pembaruan berkala.
    $teksOffline = collect($data['offline'])->pluck('teks')->implode(' ') ?: null;
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
        {{-- Bentuk penanda sama dengan PENANDA di resources/js/ews/lib/peta.js. --}}
        <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm" aria-label="Arti penanda di peta">
            <li class="flex items-center gap-2"><svg width="20" height="20" viewBox="0 0 30 30" aria-hidden="true"><circle cx="15" cy="15" r="12" fill="#1e40af"/><circle cx="15" cy="15" r="4" fill="#fff"/></svg>EWS pintar, terhubung ke web</li>
            <li class="flex items-center gap-2"><svg width="20" height="20" viewBox="0 0 30 30" aria-hidden="true"><rect x="4" y="4" width="22" height="22" rx="3" fill="#0f766e"/><path d="M15 9v7M15 20v1.5" stroke="#fff" stroke-width="3" stroke-linecap="round"/></svg>EWS alarm, belum terhubung</li>
            <li class="flex items-center gap-2"><svg width="20" height="20" viewBox="0 0 30 30" aria-hidden="true"><path d="M15 3 28 26H2Z" fill="#c2410c" stroke="#c2410c" stroke-width="2" stroke-linejoin="round"/><path d="M15 12v7M15 22v1" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/></svg>Titik rawan longsor</li>
        </ul>
    </section>

    {{-- ══════════════════════════════════════
         PETA KERAWANAN (gambar dari kajian 2024)
    ══════════════════════════════════════ --}}
    <section id="kerawanan" class="mt-8 scroll-mt-20" aria-labelledby="judul-kerawanan">
        <div class="mb-3 flex items-center gap-3">
            <span class="h-6 w-1.5 rounded-full bg-aksen" aria-hidden="true"></span>
            <h2 id="judul-kerawanan" class="text-xl font-extrabold text-navy">Peta kerawanan longsor desa</h2>
        </div>
        <p class="mb-4 flex items-start gap-2 text-sm text-redup">
            <x-ikon nama="info" class="mt-0.5 size-4 shrink-0 text-biru" />
            Gambaran kerawanan jangka panjang dari kajian tahun 2024, bukan status saat ini. Status saat ini ada di panel paling atas.
        </p>

        {{-- Peta pertama (gabungan semua faktor) dibuat besar; empat peta faktor pendukung lebih kecil di sampingnya.
             Diketuk membuka penampil layar penuh yang bisa digeser antar peta (resources/js/profil/galeri.js),
             karena tulisan legenda terlalu kecil di HP. Tanpa JavaScript, tautan membuka gambar penuh di tab baru. --}}
        <ul data-galeri data-stagger class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            @foreach ($petaKerawanan as $peta)
                @php $url = asset('images/lumeneng/peta/'.$peta['berkas'].'.webp'); @endphp
                <li @class(['col-span-2 lg:row-span-2' => $loop->first]) style="--i: {{ $loop->index }}">
                    <a href="{{ $url }}" target="_blank" rel="noopener" data-foto data-besar="{{ $url }}" data-judul="{{ $peta['judul'] }}" data-keterangan="{{ $peta['penjelasan'] }}"
                       aria-label="Perbesar {{ $peta['judul'] }}"
                       class="group flex h-full flex-col overflow-hidden rounded-2xl border border-garis-halus bg-permukaan shadow-kartu transition-[transform,box-shadow] duration-300 motion-safe:hover:-translate-y-1 hover:shadow-angkat focus-visible:outline-offset-4">
                        {{-- Peta besar mengisi sisa tinggi kartu (dua baris); peta kecil memakai perbandingan tetap. --}}
                        <span @class(['relative block bg-white', 'aspect-[7/5] lg:aspect-auto lg:min-h-0 lg:flex-1' => $loop->first, 'aspect-[7/5]' => ! $loop->first])>
                            <img src="{{ $url }}" alt="{{ $peta['alt'] }}" loading="lazy" decoding="async" width="580" height="410"
                                 class="absolute inset-0 size-full object-contain p-2 motion-safe:transition-transform motion-safe:duration-500 group-hover:scale-[1.03]">
                            <span @class([
                                'absolute right-2 top-2 inline-flex items-center justify-center rounded-full bg-navy/85 text-sm font-bold text-white',
                                'min-h-9 gap-1.5 px-3' => $loop->first,
                                'size-9' => ! $loop->first,
                            ]) aria-hidden="true">
                                <x-ikon nama="perbesar" class="size-4" />@if ($loop->first)Perbesar @endif
                            </span>
                        </span>
                        <span class="block border-t border-garis-halus p-3.5 sm:p-4">
                            <span class="flex items-center gap-2 font-judul font-extrabold leading-snug text-navy">
                                @if ($loop->first)
                                    <span class="inline-flex shrink-0 rounded-full bg-aksen-lembut px-2 py-0.5 text-xs font-bold text-aksen-tua">Utama</span>
                                @endif
                                {{ $peta['judul'] }}
                            </span>
                            <span @class(['mt-1 block text-sm text-redup', 'line-clamp-2' => ! $loop->first])>{{ $peta['penjelasan'] }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <p class="mt-3 text-sm text-redup">Sumber: {{ \App\Services\Ews\PetaKerawanan::SUMBER }}.</p>
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
