@extends('layouts.admin')
@section('judul', 'Beranda')

@php
    $ikonStatus = ['hijau' => 'aman', 'kuning' => 'waspada', 'merah' => 'awas', 'kosong' => 'kosong'];
    $warna = $status['warna'] ?? 'kosong';
    $kartuAngka = collect([
        ['menunggu', 'Menunggu validasi', 'Data sensor perlu diperiksa', 'admin.ews.validasi.index', 'validasi'],
        ['berita', 'Berita terbit', ($angka['draf'] ?? 0).' draf belum terbit', 'admin.profil.berita.index', 'berita'],
        ['foto', 'Foto di galeri', 'Dokumentasi dan foto desa', 'admin.profil.galeri.index', 'galeri'],
        ['produk', 'Produk tampil', 'Di Toko Desa', 'admin.toko.produk.index', 'produk'],
        ['materi', 'Materi edukasi', 'Sudah terbit untuk warga', 'admin.edukasi.materi.index', 'buku'],
    ])->filter(fn ($k) => $angka[$k[0]] !== null && Route::has($k[3]));
    $pintasan = collect([
        ['admin.profil.berita.create', 'Tulis berita', 'tulis'],
        ['admin.profil.galeri.create', 'Tambah foto desa', 'unggah'],
        ['admin.toko.produk.create', 'Tambah produk', 'produk'],
        ['admin.toko.panen-kopi.create', 'Catat panen kopi', 'kopi'],
        ['admin.edukasi.materi.create', 'Tulis materi edukasi', 'buku'],
        ['admin.desain.index', 'Ganti gambar situs', 'desain'],
    ])->filter(fn ($p) => Route::has($p[0]));
@endphp

@section('isi')
    {{-- Sapaan. Tombol Lihat situs dan Desain Web tidak diulang di sini: sudah ada di sidebar dan Pintasan. --}}
    <section class="pola-kontur relative mb-5 overflow-hidden rounded-2xl bg-navy px-5 py-5 text-white shadow-kartu sm:px-7 sm:py-6">
        <p class="kicker kicker--terang">Panel pengelola</p>
        <h1 class="mt-1.5 text-2xl font-extrabold sm:text-3xl">Beranda admin</h1>
        <p class="mt-1 text-white/85">Selamat datang, <span class="font-bold text-white">{{ auth()->user()->name }}</span>.
            <span class="whitespace-nowrap">{{ now()->translatedFormat('l, d F Y') }}</span></p>
    </section>

    <div class="space-y-5">
        {{-- Status longsor: kotak padat dengan warna + ikon + kata.
             Di HP isinya ditumpuk (teks selebar kotak, tombol di bawahnya) agar kalimat tidak terpotong-potong. --}}
        @if ($modul['ews']['tersedia'] ?? false)
            <section data-warna="{{ $warna }}" aria-labelledby="judul-status-ews" class="status-pekat rounded-2xl p-4 shadow-kartu sm:p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-start gap-3 sm:items-center sm:gap-4">
                        <x-ikon :nama="$ikonStatus[$warna]" class="size-9 sm:size-11" />
                        <div class="min-w-0">
                            <h2 id="judul-status-ews" class="text-xs font-bold uppercase tracking-[0.08em] opacity-90 sm:text-sm">Status peringatan dini</h2>
                            <p class="font-judul text-2xl font-extrabold leading-tight sm:text-3xl">{{ $status['status'] ?? 'Belum ada data' }}</p>
                            @if ($status && ($status['lokasi'] || $status['waktu']))
                                <p class="mt-1 text-sm leading-snug opacity-95">
                                    @if ($status['lokasi'])<span class="block sm:inline">{{ $status['lokasi'] }}</span>@endif
                                    @if ($status['lokasi'] && $status['waktu'])<span class="hidden sm:inline" aria-hidden="true"> · </span>@endif
                                    @if ($status['waktu'])<span class="block sm:inline">Data terakhir <span class="whitespace-nowrap">{{ $status['waktu'] }} WIB</span></span>@endif
                                </p>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('admin.ews.validasi.index') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-black/20 px-4 text-center font-bold hover:bg-black/30 focus-visible:outline-current">Validasi dan Awas <x-ikon nama="panah" class="size-4" /></a>
                </div>
            </section>
        @endif

        {{-- Angka ringkas. Kartu melebar mengisi baris (flex-wrap + grow) sehingga baris terakhir tidak bolong,
             dan lebar minimum 18rem (3 kartu per baris di layar lebar) menjaga teks tidak terpecah satu kata per baris. --}}
        @if ($kartuAngka->isNotEmpty())
            <section aria-labelledby="judul-angka">
                <h2 id="judul-angka" class="sr-only">Ringkasan</h2>
                <ul class="flex flex-wrap gap-4">
                    @foreach ($kartuAngka as [$kunci, $label, $ket, $rute, $ikon])
                        <li class="min-w-0 grow basis-full sm:basis-[18rem]">
                            <a href="{{ route($rute) }}" class="kartu-angkat group flex h-full flex-col gap-3 rounded-[0.9rem] border border-garis-halus bg-permukaan p-4 shadow-kartu sm:p-5">
                                <span class="flex items-center gap-3">
                                    <span @class([
                                        'flex size-10 shrink-0 items-center justify-center rounded-xl',
                                        'bg-waspada-lembut text-waspada-teks' => $kunci === 'menunggu' && $angka[$kunci] > 0,
                                        'bg-biru-muda text-biru' => ! ($kunci === 'menunggu' && $angka[$kunci] > 0),
                                    ])>
                                        @include('layouts.partials.ikon-menu', ['nama' => $ikon])
                                    </span>
                                    <span class="min-w-0 font-bold leading-snug text-redup">{{ $label }}</span>
                                </span>
                                <span class="block font-judul text-4xl font-extrabold leading-none text-navy group-hover:text-biru">{{ number_format($angka[$kunci], 0, ',', '.') }}</span>
                                <span class="block text-sm leading-snug text-redup">{{ $ket }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Pintasan: ubin berjajar selebar halaman, bukan kolom tersendiri di kanan. --}}
        <x-kartu judul="Pintasan">
            <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pintasan as [$rute, $label, $ikon])
                    <li>
                        <a href="{{ route($rute) }}" class="group flex min-h-12 items-center gap-3 rounded-xl border border-garis-halus px-3 font-bold hover:border-biru hover:bg-biru-lembut hover:text-biru">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-krem text-biru group-hover:bg-biru group-hover:text-white">
                                @include('layouts.partials.ikon-menu', ['nama' => $ikon])
                            </span>
                            <span class="min-w-0 leading-snug">{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-kartu>

        {{-- Status modul --}}
        <x-kartu judul="Status modul">
            <ul class="grid gap-x-6 sm:grid-cols-2">
                @foreach ($modul as $m)
                    <li class="flex items-center justify-between gap-3 border-b border-garis-halus py-3">
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $m['tersedia'] ? 'bg-biru-muda text-biru' : 'bg-kosong-lembut text-redup' }}">
                                <x-ikon :nama="$m['ikon']" class="size-5" />
                            </span>
                            <span class="min-w-0 font-bold leading-snug">{{ $m['nama'] }}</span>
                        </span>
                        @if ($m['tersedia'])
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-aman-lembut px-3 py-1 text-sm font-bold text-aman">
                                <x-ikon nama="aman" class="size-4" /> Aktif
                            </span>
                        @else
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-kosong-lembut px-3 py-1 text-sm font-bold text-redup">
                                <x-ikon nama="kosong" class="size-4" /> Belum aktif
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-kartu>

        <p class="flex items-start gap-2 rounded-[0.9rem] border border-aksen/25 bg-aksen-lembut px-4 py-3 text-sm text-tinta">
            <x-ikon nama="galeri" class="mt-0.5 size-5 text-aksen-tua" />
            <span>{{ $angka['slot'] }} gambar tata letak memakai foto sendiri. Ganti foto hero, sampul, dan latar di menu <a href="{{ route('admin.desain.index') }}" class="tautan">Desain Web</a>.</span>
        </p>
    </div>
@endsection
