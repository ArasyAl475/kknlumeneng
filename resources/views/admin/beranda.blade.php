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
    {{-- Sapaan --}}
    <section class="pola-kontur relative mb-6 overflow-hidden rounded-2xl bg-navy p-6 text-white shadow-kartu sm:p-8">
        <p class="kicker kicker--terang">Panel pengelola</p>
        <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">Beranda admin</h1>
        <p class="mt-1 text-white/85">Selamat datang, <span class="font-bold text-white">{{ auth()->user()->name }}</span>. Hari ini {{ now()->translatedFormat('l, d F Y') }}.</p>
        <div class="mt-5 flex flex-wrap gap-2">
            <x-tombol varian="terang" :href="route('landing')" target="_blank" rel="noopener"><x-ikon nama="beranda" class="size-5" />Lihat situs</x-tombol>
            <x-tombol varian="kaca" :href="route('admin.desain.index')"><x-ikon nama="galeri" class="size-5" />Desain Web</x-tombol>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-6">
            {{-- Status longsor: kotak padat dengan warna + ikon + kata --}}
            @if ($modul['ews']['tersedia'] ?? false)
                <section data-warna="{{ $warna }}" aria-labelledby="judul-status-ews" class="status-pekat flex flex-wrap items-center gap-4 rounded-2xl p-5 shadow-kartu">
                    <x-ikon :nama="$ikonStatus[$warna]" class="size-10 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <h2 id="judul-status-ews" class="text-sm font-bold uppercase tracking-[0.1em] opacity-85">Status peringatan dini</h2>
                        <p class="font-judul text-2xl font-extrabold">{{ $status['status'] ?? 'Belum ada data' }}</p>
                        @if ($status && ($status['lokasi'] || $status['waktu']))
                            <p class="text-sm opacity-90">{{ $status['lokasi'] }}{{ $status['lokasi'] && $status['waktu'] ? ' · ' : '' }}{{ $status['waktu'] ? 'data terakhir '.$status['waktu'].' WIB' : '' }}</p>
                        @endif
                    </div>
                    <a href="{{ route('admin.ews.validasi.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-black/15 px-4 font-bold hover:bg-black/25 focus-visible:outline-current">Validasi dan Awas <x-ikon nama="panah" class="size-4" /></a>
                </section>
            @endif

            {{-- Angka ringkas --}}
            @if ($kartuAngka->isNotEmpty())
                <section aria-labelledby="judul-angka">
                    <h2 id="judul-angka" class="sr-only">Ringkasan</h2>
                    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($kartuAngka as [$kunci, $label, $ket, $rute, $ikon])
                            <li>
                                <a href="{{ route($rute) }}" class="kartu-angkat group flex h-full items-start gap-4 rounded-[0.9rem] border border-garis-halus bg-permukaan p-5 shadow-kartu">
                                    <span @class([
                                        'flex size-12 shrink-0 items-center justify-center rounded-xl',
                                        'bg-waspada-lembut text-waspada-teks' => $kunci === 'menunggu' && $angka[$kunci] > 0,
                                        'bg-biru-muda text-biru' => ! ($kunci === 'menunggu' && $angka[$kunci] > 0),
                                    ])>
                                        @include('layouts.partials.ikon-menu', ['nama' => $ikon])
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-bold text-redup">{{ $label }}</span>
                                        <span class="block font-judul text-3xl font-extrabold leading-tight text-navy group-hover:text-biru">{{ number_format($angka[$kunci], 0, ',', '.') }}</span>
                                        <span class="block text-sm text-redup">{{ $ket }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Status modul --}}
            <x-kartu judul="Status modul">
                <ul class="grid gap-x-6 sm:grid-cols-2">
                    @foreach ($modul as $m)
                        <li class="flex items-center justify-between gap-3 border-b border-garis-halus py-3">
                            <span class="flex items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $m['tersedia'] ? 'bg-biru-muda text-biru' : 'bg-kosong-lembut text-redup' }}">
                                    <x-ikon :nama="$m['ikon']" class="size-5" />
                                </span>
                                <span class="font-bold">{{ $m['nama'] }}</span>
                            </span>
                            @if ($m['tersedia'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-aman-lembut px-3 py-1 text-sm font-bold text-aman">
                                    <x-ikon nama="aman" class="size-4" /> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-kosong-lembut px-3 py-1 text-sm font-bold text-redup">
                                    <x-ikon nama="kosong" class="size-4" /> Belum aktif
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-kartu>
        </div>

        {{-- Pintasan --}}
        <aside class="space-y-6">
            <x-kartu judul="Pintasan">
                <ul class="space-y-1">
                    @foreach ($pintasan as [$rute, $label, $ikon])
                        <li>
                            <a href="{{ route($rute) }}" class="group flex min-h-12 items-center gap-3 rounded-xl px-2 font-bold hover:bg-biru-lembut hover:text-biru">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-krem text-biru group-hover:bg-biru group-hover:text-white">
                                    @include('layouts.partials.ikon-menu', ['nama' => $ikon])
                                </span>
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-kartu>

            <div class="rounded-[0.9rem] border border-aksen/25 bg-aksen-lembut p-5">
                <p class="flex items-center gap-2 font-judul font-extrabold text-aksen-tua"><x-ikon nama="galeri" class="size-5" />Tampilan situs</p>
                <p class="mt-1 text-sm text-tinta">{{ $angka['slot'] }} gambar tata letak memakai foto sendiri. Ganti foto hero, sampul, dan latar di menu Desain Web.</p>
            </div>
        </aside>
    </div>
@endsection
