{{-- Kerangka halaman hasil panen: header dan footer mengikuti rancangan Stitch "AgroLumeneng Rural Intelligence".
     Sengaja terpisah dari layouts/app agar berkas bersama tidak berubah; gabungkan lewat PR fondasi bila ingin dipakai semua halaman. --}}
@php
    $ews = \App\Support\Modul::tersedia()['ews'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('judul') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('deskripsi')">
    <meta name="theme-color" content="#00361f">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>document.documentElement.classList.add('js');</script>
    @vite(['resources/css/app.css', 'resources/js/toko/hasil.js'])
    @stack('kepala')
</head>
<body class="hasil">
    <a href="#isi" class="lompat">Lompat ke isi</a>

    <header class="hasil-kepala">
        <div class="hasil-lebar hasil-kepala__baris">
            <a href="{{ route('landing') }}" class="hasil-merek">
                <span class="hasil-merek__ikon">@include('toko.partials.ikon', ['nama' => 'pohon', 'kelas' => 'size-6'])</span>
                <span class="hasil-merek__teks">
                    <span class="hasil-merek__baris">
                        <span class="hasil-merek__nama">Desa Lumeneng</span>
                        <span class="lencana lencana--mint hasil-merek__lencana">Agraria Terpadu</span>
                    </span>
                    <span class="hasil-merek__wilayah">Kec. Paninggaran, Kab. Pekalongan</span>
                </span>
            </a>
            @if ($ews)
                <a href="{{ route($ews['rute']) }}" class="tombol-peringatan">
                    @include('toko.partials.ikon', ['nama' => 'peringatan', 'kelas' => 'size-5'])
                    {{ $ews['nama'] }}
                </a>
            @endif
        </div>
    </header>

    <main id="isi" class="hasil-lebar hasil-isi">
        @yield('isi')
    </main>

    <footer class="hasil-kaki">
        <div class="hasil-lebar hasil-kaki__isi">
            <div class="hasil-kaki__kolom hasil-kaki__merek">
                <p class="hasil-kaki__nama">
                    <span class="hasil-merek__ikon">@include('toko.partials.ikon', ['nama' => 'pohon', 'kelas' => 'size-6'])</span>
                    Desa Lumeneng
                </p>
                <p>Kec. Paninggaran, Kab. Pekalongan,<br>Jawa Tengah</p>
                <p class="hasil-kaki__redup">Program KKN / Aksara Peduli</p>
            </div>

            <nav aria-labelledby="judul-kaki-tautan" class="hasil-kaki__kolom">
                <h2 id="judul-kaki-tautan" class="hasil-kaki__judul">Jelajahi</h2>
                <ul class="hasil-kaki__daftar">
                    <li><a href="{{ route('landing') }}">Beranda</a></li>
                    <li><a href="{{ route('toko.index') }}">Katalog Produk</a></li>
                    @if ($ews)
                        <li><a href="{{ route($ews['rute']) }}">{{ $ews['nama'] }}</a></li>
                    @endif
                </ul>
            </nav>

            {{-- Pernyataan peringatan dini wajib tampil jelas (CLAUDE.md), walau footer Stitch tidak memuatnya. --}}
            <div class="hasil-kaki__kolom">
                <h2 class="hasil-kaki__judul">Catatan penting</h2>
                <p class="hasil-kaki__catatan">
                    @include('toko.partials.ikon', ['nama' => 'peringatan', 'kelas' => 'size-5'])
                    <span>
                        Informasi peringatan dini di situs ini adalah <strong>alat bantu</strong>, bukan peringatan resmi BPBD.
                        Saat kondisi berbahaya, segera menjauh dan hubungi perangkat desa.
                    </span>
                </p>
            </div>
        </div>

        <div class="hasil-kaki__bawah">
            <div class="hasil-lebar hasil-kaki__bawah-isi">
                <p>&copy; {{ now()->year }} Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan.</p>
                <a href="#isi" class="hasil-kaki__atas">
                    Kembali ke atas
                    @include('toko.partials.ikon', ['nama' => 'naik', 'kelas' => 'size-4'])
                </a>
            </div>
        </div>
    </footer>

    @stack('skrip')
</body>
</html>
