@extends('layouts.app')
@section('judul', 'Profil '.$profil->namaDesa())
@section('deskripsi', 'Mengenal '.$profil->namaDesa().', '.$profil->lokasi().': sambutan kepala desa, sejarah, visi misi, struktur pemerintahan, dan kontak kantor desa.')

@php
    $statistik = $profil->statistik();
    $batas = $profil->batas();
    $misi = $profil->misi();
    $adaBatas = $profil->ada('batas_utara', 'batas_selatan', 'batas_timur', 'batas_barat');
    $adaKontak = $profil->ada('alamat_kantor', 'telepon', 'email', 'jam_pelayanan') || $profil->tautanPeta();
    $adaSambutan = $profil->ada('sambutan', 'nama_kepala_desa');
    $kosongSemua = ! $adaSambutan && ! $statistik && ! $profil->ada('sejarah') && ! $adaBatas && ! $adaKontak;

    $kepala = $perangkat->get('kepala', collect());
    $sekretaris = $perangkat->get('sekretaris', collect());
    // Tiga kelompok pelaksana tampil berdampingan di bawah sekretaris desa.
    $pelaksana = collect([
        'kaur' => ['Sekretariat', 'Kepala Urusan'],
        'kasi' => ['Pelaksana Teknis', 'Kepala Seksi'],
        'kadus' => ['Pelaksana Kewilayahan', 'Kepala Dusun'],
    ])->filter(fn ($judul, $kelompok) => $perangkat->has($kelompok));
@endphp

@section('hero')
    @include('profil.partials.sampul')
@endsection

@section('isi-rapat', true)
@section('header-transparan', true)

@section('isi')
    @include('profil.partials.tab')

    {{-- ═══════════════ 1. TENTANG DESA ═══════════════ --}}
    <section id="tentang" aria-labelledby="judul-tentang" class="scroll-mt-20">
        <h2 id="judul-tentang" class="sr-only">Tentang desa</h2>

        @if ($kosongSemua)
            <x-kartu class="text-center">
                <x-ikon nama="desa" class="mx-auto size-10 text-biru" />
                <p class="mt-2 text-lg font-bold">Profil desa sedang disiapkan.</p>
                <p class="mt-1 text-redup">Perangkat desa sedang melengkapi isinya. Silakan kembali lagi nanti.</p>
            </x-kartu>
        @endif

        {{-- Sambutan: foto kepala desa di kiri, isi sambutan di kanan --}}
        @if ($adaSambutan)
            <article aria-labelledby="judul-sambutan" class="grid items-center gap-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]" data-muncul>
                <figure class="relative mx-auto w-full max-w-sm">
                    <span class="absolute -inset-3 -z-10 rotate-3 rounded-[2rem] bg-gradient-to-br from-aksen-terang/60 to-biru-muda" aria-hidden="true"></span>
                    <img src="{{ site_image('profil.kepala-desa') }}" alt="{{ $profil->ada('nama_kepala_desa') ? 'Foto '.$profil->nilai('nama_kepala_desa') : site_image_alt('profil.kepala-desa') }}" loading="lazy" decoding="async" class="aspect-[4/5] w-full rounded-[1.75rem] object-cover shadow-angkat">
                    @if ($profil->ada('nama_kepala_desa'))
                        <figcaption class="kaca absolute inset-x-4 bottom-4 rounded-2xl px-4 py-3 text-white">
                            <span class="block font-judul text-lg font-extrabold leading-snug">{{ $profil->nilai('nama_kepala_desa') }}</span>
                            <span class="block text-sm font-bold text-aksen-terang">Kepala {{ $profil->namaDesa() }}</span>
                        </figcaption>
                    @endif
                </figure>
                <div class="relative">
                    <span class="pointer-events-none absolute -top-10 right-0 font-serif text-[9rem] leading-none text-biru/10" aria-hidden="true">&rdquo;</span>
                    <p class="kicker">Sambutan</p>
                    <h3 id="judul-sambutan" class="mt-2 text-[clamp(1.7rem,4vw,2.5rem)] font-extrabold leading-tight text-navy">Sugeng rawuh di {{ $profil->namaDesa() }}</h3>
                    @if ($profil->ada('sambutan'))
                        {{-- Sudah dibersihkan IsiKaya, jadi aman ditampilkan sebagai HTML. --}}
                        <div class="isi-kaya mt-4 text-redup">{{ $profil->isiKaya('sambutan') }}</div>
                    @else
                        <p class="mt-4 text-redup">Sambutan kepala desa belum diisi.</p>
                    @endif
                </div>
            </article>
        @endif

        {{-- Desa dalam angka: pita putih bergradasi --}}
        @if ($statistik)
            <div class="latar-pudar mt-6 [--ke:#ffffff] [--pudar:6rem]">
                <div class="mb-6 text-center" data-muncul>
                    <p class="kicker justify-center">Data wilayah</p>
                    <h3 class="mt-2 text-[clamp(1.6rem,3.6vw,2.2rem)] font-extrabold text-navy">Desa dalam angka</h3>
                </div>
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4" data-stagger>
                    @foreach ($statistik as $angka)
                        <li class="group rounded-2xl border border-garis-halus bg-gradient-to-br from-permukaan to-biru-lembut p-5 shadow-kartu transition-[transform,box-shadow] duration-300 motion-safe:hover:-translate-y-1 hover:shadow-angkat" style="--i: {{ $loop->index }}">
                            <p class="text-sm font-bold text-redup">{{ $angka['label'] }}</p>
                            <p class="mt-2 flex flex-wrap items-baseline gap-x-1.5">
                                <span class="font-judul text-[clamp(1.8rem,6vw,2.5rem)] font-extrabold leading-none text-biru tabular-nums" data-hitung>{{ $angka['nilai'] }}</span>
                                <span class="text-sm font-bold text-aksen-tua">{{ $angka['satuan'] }}</span>
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Sejarah + batas wilayah --}}
        @if ($profil->ada('sejarah') || $adaBatas)
            <div @class(['mt-14 grid items-start gap-8', 'lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]' => $profil->ada('sejarah') && $adaBatas])>
                @if ($profil->ada('sejarah'))
                    <article aria-labelledby="judul-sejarah" data-muncul>
                        <p class="kicker">Asal-usul</p>
                        <h3 id="judul-sejarah" class="mt-2 text-[clamp(1.6rem,3.6vw,2.2rem)] font-extrabold text-navy">Sejarah desa</h3>
                        <div class="isi-kaya mt-4">{{ $profil->isiKaya('sejarah') }}</div>
                    </article>
                @endif

                @if ($adaBatas)
                    <article aria-labelledby="judul-batas" class="rounded-3xl border border-garis-halus bg-gradient-to-br from-permukaan to-krem p-6 shadow-kartu sm:p-7" data-muncul>
                        <h3 id="judul-batas" class="text-xl font-extrabold text-navy">Batas wilayah</h3>
                        <p class="mt-1 text-sm text-redup">Desa yang berbatasan langsung dengan {{ $profil->namaDesa() }}.</p>
                        {{-- Disusun seperti mata angin agar mudah dibayangkan: utara di atas, selatan di bawah. --}}
                        <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                            @foreach (['utara' => 'col-start-2', 'barat' => 'col-start-1 row-start-2', 'timur' => 'col-start-3 row-start-2', 'selatan' => 'col-start-2 row-start-3'] as $arah => $letak)
                                <div class="{{ $letak }} flex min-h-24 flex-col justify-center rounded-xl bg-biru-lembut p-2 ring-1 ring-biru/10">
                                    <p class="text-xs font-bold uppercase tracking-wider text-biru">{{ ucfirst($arah) }}</p>
                                    <p class="mt-1 text-sm font-bold leading-snug break-words">{{ $batas[$arah] ?: '–' }}</p>
                                </div>
                            @endforeach
                            <div class="col-start-2 row-start-2 flex min-h-24 flex-col items-center justify-center rounded-xl bg-gradient-to-br from-biru to-navy p-2 text-white shadow-md" aria-hidden="true">
                                <x-ikon nama="desa" class="size-6" />
                                <span class="mt-1 text-xs font-bold leading-tight">{{ $profil->nilai('nama_desa') }}</span>
                            </div>
                        </div>
                    </article>
                @endif
            </div>
        @endif
    </section>

    {{-- ═══════════════ 2. VISI & MISI: pita navy yang memudar di atas dan bawah ═══════════════ --}}
    <section id="visi-misi" aria-labelledby="judul-visi-misi" class="latar-pudar mt-8 scroll-mt-16 text-white [--ke-tengah:var(--color-biru-tua)]">
        <div class="lapis-pudar pola-kontur" aria-hidden="true"></div>
        <div class="text-center" data-muncul>
            <p class="kicker kicker--terang justify-center">Arah pembangunan</p>
            <h2 id="judul-visi-misi" class="mt-2 text-[clamp(1.8rem,4.5vw,2.8rem)] font-extrabold">Visi &amp; Misi</h2>
        </div>

        @if (! $profil->ada('visi') && ! $misi)
            <div class="kaca mx-auto mt-8 max-w-xl rounded-2xl p-6 text-center">
                <x-ikon nama="aman" class="mx-auto size-10 text-aksen-terang" />
                <p class="mt-2 text-lg font-bold">Visi dan misi belum diisi.</p>
                <p class="mt-1 text-white/80">Silakan kembali lagi nanti.</p>
            </div>
        @endif

        @if ($profil->ada('visi'))
            <figure class="relative mx-auto mt-10 max-w-4xl text-center" data-muncul>
                <span class="pointer-events-none absolute -top-16 left-1/2 -translate-x-1/2 font-serif text-[10rem] leading-none text-white/10" aria-hidden="true">&ldquo;</span>
                <p class="inline-flex items-center gap-2 rounded-full bg-aksen px-4 py-1 text-xs font-bold uppercase tracking-[0.14em]">Visi</p>
                <blockquote class="relative mt-5 font-judul text-[clamp(1.4rem,3.6vw,2.2rem)] font-bold leading-snug text-balance">{{ $profil->nilai('visi') }}</blockquote>
            </figure>
        @endif

        @if ($misi)
            <div class="mt-14">
                <h3 class="text-center text-sm font-bold uppercase tracking-[0.14em] text-aksen-terang">Misi</h3>
                <ol class="mt-5 grid gap-3 md:grid-cols-2" data-stagger>
                    @foreach ($misi as $butir)
                        <li class="kaca flex items-start gap-4 rounded-2xl p-4 transition-colors duration-300 hover:bg-white/15 sm:p-5" style="--i: {{ $loop->index }}">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white font-judul text-lg font-extrabold text-navy" aria-hidden="true">{{ $loop->iteration }}</span>
                            <p class="pt-1.5 text-lg leading-relaxed"><span class="sr-only">Misi {{ $loop->iteration }}: </span>{{ $butir }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </section>

    {{-- ═══════════════ 3. PEMERINTAHAN: bagan di atas gradasi biru muda ═══════════════ --}}
    <section id="pemerintahan" aria-labelledby="judul-struktur" class="latar-pudar mt-4 scroll-mt-16 [--ke:var(--color-biru-lembut)] [--pudar:6rem]">
        <div class="mb-8 text-center" data-muncul>
            <p class="kicker justify-center">Pemerintahan desa</p>
            <h2 id="judul-struktur" class="mt-2 text-[clamp(1.8rem,4.5vw,2.8rem)] font-extrabold text-navy">Struktur pemerintahan</h2>
            <p class="mt-2 text-redup">Susunan perangkat {{ $profil->namaDesa() }} dari atas ke bawah.</p>
        </div>

        @if ($perangkat->isEmpty())
            <x-kartu class="mx-auto max-w-xl text-center">
                <x-ikon nama="orang" class="mx-auto size-10 text-biru" />
                <p class="mt-2 text-lg font-bold">Struktur pemerintahan belum diisi.</p>
                <p class="mt-1 text-redup">Silakan kembali lagi nanti.</p>
            </x-kartu>
        @else
            <div data-muncul>
                {{-- Tingkat atas: kepala desa dan sekretaris, di tengah dengan garis penghubung. --}}
                @foreach ([$kepala, $sekretaris] as $tingkat)
                    @if ($tingkat->isNotEmpty())
                        <div class="mx-auto flex max-w-md flex-col gap-3">
                            @foreach ($tingkat as $orang)
                                @include('profil.partials.kartu-perangkat', ['besar' => $loop->parent->first])
                            @endforeach
                        </div>
                        <div class="mx-auto h-8 w-1 rounded-full bg-gradient-to-b from-biru/50 to-biru/20" aria-hidden="true"></div>
                    @endif
                @endforeach

                @if ($pelaksana->isNotEmpty())
                    {{-- Garis mendatar menghubungkan tiga kelompok; disembunyikan di HP karena kelompok tersusun ke bawah. --}}
                    <div class="mx-auto hidden h-1 w-2/3 rounded-full bg-biru/25 lg:block" aria-hidden="true"></div>
                    <div @class(['grid gap-6', 'lg:grid-cols-3' => $pelaksana->count() === 3, 'lg:grid-cols-2' => $pelaksana->count() === 2])>
                        @foreach ($pelaksana as $kelompok => [$judulKelompok, $sebutan])
                            <div>
                                <div class="mx-auto hidden h-6 w-1 rounded-full bg-biru/25 lg:block" aria-hidden="true"></div>
                                <h3 class="mb-3 rounded-xl bg-gradient-to-r from-navy to-biru px-4 py-2.5 text-center font-bold text-white shadow-sm">
                                    {{ $judulKelompok }}
                                    <span class="block text-sm font-normal text-white/80">{{ $sebutan }}</span>
                                </h3>
                                <ul class="space-y-3">
                                    @foreach ($perangkat[$kelompok] as $orang)
                                        <li>@include('profil.partials.kartu-perangkat')</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if ($lembaga->isNotEmpty())
            <div class="mt-16">
                <div class="mb-6 text-center" data-muncul>
                    <h3 class="text-[clamp(1.5rem,3.4vw,2rem)] font-extrabold text-navy">Lembaga desa</h3>
                    <p class="mt-1 text-redup">Lembaga yang bekerja bersama pemerintah desa melayani warga.</p>
                </div>
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-stagger>
                    @foreach ($lembaga as $l)
                        <li class="flex h-full flex-col rounded-2xl border border-garis-halus bg-permukaan p-5 shadow-kartu transition-[transform,box-shadow] duration-300 motion-safe:hover:-translate-y-1 hover:shadow-angkat" style="--i: {{ $loop->index }}">
                            <div class="flex items-center gap-3">
                                <span class="flex min-w-14 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-aksen-lembut to-aksen-terang/50 px-2 py-3 text-sm font-bold text-aksen-tua" aria-hidden="true">{{ $l->sebutan() }}</span>
                                <h4 class="font-judul font-extrabold leading-snug">{{ $l->nama }}</h4>
                            </div>
                            @if ($l->keterangan)
                                <p class="mt-3 flex-1 text-redup">{{ $l->keterangan }}</p>
                            @endif
                            @if ($l->ketua)
                                <p class="mt-3 flex items-center gap-2 border-t border-garis-halus pt-3 text-sm">
                                    <x-ikon nama="orang" class="size-4 text-biru" />
                                    <span><span class="text-redup">Ketua:</span> <span class="font-bold">{{ $l->ketua }}</span></span>
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    {{-- ═══════════════ KONTAK KANTOR DESA ═══════════════ --}}
    @if ($adaKontak)
        <section aria-labelledby="judul-kontak" class="pola-kontur mt-14 overflow-hidden rounded-3xl bg-gradient-to-br from-navy via-navy to-biru-tua text-white shadow-angkat" data-muncul>
            <div class="grid gap-6 p-6 sm:p-10 md:grid-cols-2">
                <div>
                    <p class="kicker kicker--terang">Pelayanan warga</p>
                    <h2 id="judul-kontak" class="mt-2 text-2xl font-extrabold sm:text-3xl">Kantor {{ $profil->namaDesa() }}</h2>
                    @if ($profil->ada('alamat_kantor'))
                        <p class="mt-4 flex items-start gap-3 text-white/90"><x-ikon nama="lokasi" class="mt-0.5 size-5" /><span>{{ $profil->nilai('alamat_kantor') }}</span></p>
                    @endif
                    @if ($profil->tautanPeta())
                        <x-tombol varian="terang" :href="$profil->tautanPeta()" target="_blank" rel="noopener" class="mt-5 min-h-12">
                            <x-ikon nama="peta" class="size-5" /><span>Buka di Google Maps</span>
                        </x-tombol>
                    @endif
                </div>
                <ul class="space-y-3">
                    @if ($profil->teleponTautan())
                        <li>
                            <a href="tel:{{ $profil->teleponTautan() }}" class="flex min-h-14 items-center gap-3 rounded-xl bg-white/10 px-4 py-2 font-bold transition-colors duration-200 hover:bg-white/20 focus-visible:outline-white">
                                <x-ikon nama="telepon" class="size-5 shrink-0" />
                                <span><span class="block text-xs font-normal text-white/70">Telepon kantor desa</span>{{ $profil->nilai('telepon') }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($profil->ada('email'))
                        <li>
                            <a href="mailto:{{ $profil->nilai('email') }}" class="flex min-h-14 items-center gap-3 rounded-xl bg-white/10 px-4 py-2 font-bold break-all transition-colors duration-200 hover:bg-white/20 focus-visible:outline-white">
                                <x-ikon nama="surel" class="size-5 shrink-0" />
                                <span><span class="block text-xs font-normal text-white/70">Surel</span>{{ $profil->nilai('email') }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($profil->ada('jam_pelayanan'))
                        <li class="flex items-start gap-3 rounded-xl bg-white/10 px-4 py-3">
                            <x-ikon nama="jam" class="mt-0.5 size-5 shrink-0" />
                            <span>
                                <span class="block text-xs text-white/70">Jam pelayanan</span>
                                @foreach ($profil->baris('jam_pelayanan') as $baris)
                                    <span class="block font-bold">{{ $baris }}</span>
                                @endforeach
                            </span>
                        </li>
                    @endif
                </ul>
            </div>
        </section>
    @endif
@endsection
