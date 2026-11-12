@extends('layouts.app')
@section('judul', __('landing.judul_halaman'))
@section('deskripsi', __('landing.deskripsi_meta'))

@php
    $ews = $modul['ews'] ?? null;
    $adaEws = $ews && $ews['tersedia'];
    $toko = $modul['toko'] ?? null;
    $limbah = $modul['limbah'] ?? null;
    $ikonStatus = ['hijau' => 'aman', 'kuning' => 'waspada', 'merah' => 'awas', 'kosong' => 'kosong'];
    $warna = $status['warna'] ?? 'kosong';
    $statistik = $profil ? array_slice($profil->statistik(), 0, 4) : [];
    $tagline = $profil?->nilai('tagline') ?: 'Desa di lereng perbukitan Paninggaran: kebun kopi, jamur tiram, dan warga yang siaga bencana.';
    $saranStatus = [
        'hijau' => 'Kondisi lereng terpantau normal. Tetap waspada saat hujan deras berjam-jam.',
        'kuning' => 'Tanah mulai jenuh air atau lereng mulai bergerak. Hindari lereng dan siapkan jalur evakuasi.',
        'merah' => 'Bahaya longsor. Segera menjauh dari lereng dan ikuti arahan perangkat desa.',
        'kosong' => 'Sensor belum mengirim data. Pastikan alat sudah terpasang.',
    ];
    $sensorBeranda = [
        ['tanah', 'Kelembapan tanah', 'Tanah terlalu basah lebih mudah bergerak.'],
        ['hujan', 'Curah hujan', 'Hujan deras menambah beban lereng.'],
        ['miring', 'Kemiringan', 'Perubahan sudut kecil bisa jadi tanda awal.'],
        ['udara', 'Kelembapan udara', 'Membantu membaca cuaca sekitar lereng.'],
    ];
    // Bento: ukuran kartu berbeda-beda agar tidak monoton. Kartu berfoto memakai slot Desain Web.
    $bento = [
        'profil' => ['kelas' => 'col-span-2 lg:row-span-2', 'gambar' => 'profil.hero', 'besar' => true],
        'ews' => ['kelas' => 'col-span-2', 'gambar' => null, 'gelap' => true],
        'berita' => ['kelas' => '', 'gambar' => null],
        'galeri' => ['kelas' => '', 'gambar' => 'galeri.hero'],
        'toko' => ['kelas' => 'col-span-2', 'gambar' => 'toko.hero'],
        'edukasi' => ['kelas' => '', 'gambar' => null],
        'limbah' => ['kelas' => '', 'gambar' => null],
    ];
    $beritaUtama = $beritaTerbaru->first();
@endphp

@push('skrip')
    @vite('resources/js/landing/hero-gulir.js')
@endpush

{{-- Status longsor di hero dan kartu "Kondisi lereng" berubah sendiri tanpa muat ulang halaman. --}}
@if ($adaEws)
    @push('skrip')
        @vite('resources/js/landing/status-langsung.js')
    @endpush
@endif

@push('skrip')
    @vite('resources/js/landing/bentang-3d.js')
@endpush

@if ($fotoTerbaru->isNotEmpty())
    @push('skrip')
        @vite('resources/js/profil/galeri.js')
    @endpush
@endif

{{-- ══════════ HERO: foto penuh, teks di kiri, bagian bawah memudar ke krem ══════════ --}}
@section('hero')
    {{-- data-hero-gulir: saat digulir ke bawah, teks hero memudar sambil naik dan sedikit bergeser ke samping
         (resources/js/landing/hero-gulir.js mengisi --p, gayanya ada di app.css bagian .hero-geser).
         Tiap .hero-geser membungkus satu bagian; animasi muncul-awal tetap di elemen dalamnya agar tidak bentrok. --}}
    <section data-hero-gulir class="relative isolate flex min-h-[min(86svh,56rem)] items-center overflow-hidden bg-navy text-white" aria-labelledby="judul-hero">
        <x-gambar-situs kunci="beranda.hero" :prioritas="true" class="hero-dekat absolute inset-0 -z-20 size-full" />
        {{-- Navy dari kiri agar teks terbaca, lalu memudar ke krem di bawah: tidak ada batas lurus ke bagian berikutnya. --}}
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-navy-tua/90 via-navy/55 to-navy/10" aria-hidden="true"></div>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-2/5 bg-gradient-to-b from-transparent via-navy/30 to-kertas" aria-hidden="true"></div>

        <div class="teks-di-foto mx-auto w-full max-w-6xl px-4 pb-32 pt-32 sm:pt-36">
            <div class="max-w-2xl">
                {{-- --d = kapan bagian ini mulai memudar (0 sampai 1), --gx/--gy = jarak geser akhirnya.
                     Bagian atas pergi lebih dulu dan geserannya pendek; makin ke bawah makin jauh dan menyamping. --}}
                <div class="hero-geser" style="--d:0;--gx:14px;--gy:-56px">
                    <p class="kaca-terang inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold motion-safe:animate-muncul">
                        <x-ikon nama="lokasi" class="size-4 text-aksen-terang" />
                        Kec. Paninggaran · Kab. Pekalongan
                    </p>
                </div>
                <h1 id="judul-hero" class="mt-5 leading-none motion-safe:animate-muncul [animation-delay:90ms]">
                    <span class="hero-geser block" style="--d:0.04;--gx:22px;--gy:-72px"><span class="block text-[clamp(1.4rem,4.5vw,2.2rem)] font-medium tracking-tight text-white/90">Selamat datang di Desa</span></span>
                    <span class="hero-geser mt-1 block" style="--d:0.08;--gx:40px;--gy:-104px"><span class="block text-[clamp(3.2rem,12vw,6.8rem)] font-extrabold tracking-[-0.04em]">Lumeneng</span></span>
                </h1>
                <div class="hero-geser mt-5" style="--d:0.12;--gx:50px;--gy:-112px">
                    <p class="max-w-xl text-lg text-white/90 sm:text-xl motion-safe:animate-muncul [animation-delay:180ms]">{{ $tagline }}</p>
                </div>

                <div class="hero-geser mt-8" style="--d:0.16;--gx:62px;--gy:-124px">
                    <div class="flex flex-wrap gap-3 motion-safe:animate-muncul [animation-delay:270ms]">
                        @if ($modul['profil']['tersedia'] ?? false)
                            <x-tombol varian="aksen" :href="route('profil.index')" class="min-h-13 rounded-full px-7 text-lg shadow-lg">
                                Jelajahi Desa <x-ikon nama="panah" class="size-5" />
                            </x-tombol>
                        @endif
                        <x-tombol varian="kaca" href="#layanan" class="min-h-13 rounded-full px-6 text-lg">
                            <x-ikon nama="menu" class="size-5" /><span>Lihat layanan</span>
                        </x-tombol>
                    </div>
                </div>

                {{-- Pil status ringkas: status longsor langsung terbaca di HP tanpa menggulir. Warna padat, bukan kaca. --}}
                @if ($adaEws)
                    <div class="hero-geser mt-7" style="--d:0.2;--gx:72px;--gy:-136px">
                        <a id="chip-status" href="#kondisi" data-warna="{{ $warna }}" class="status-pekat inline-flex min-h-11 items-center gap-2.5 rounded-full py-2 pl-3 pr-4 font-bold shadow-lg [text-shadow:none] motion-safe:animate-muncul [animation-delay:360ms] hover:brightness-110">
                            <x-ikon :nama="$ikonStatus[$warna]" class="size-5 shrink-0" />
                            <span data-isi="chip-teks">Status longsor: {{ $status['status'] ?? 'Belum ada data' }}</span>
                            <x-ikon nama="panah" class="size-4 rotate-90" />
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Penanda gulir hilang paling dulu begitu halaman mulai digulir. --}}
        <div class="hero-geser absolute bottom-24 left-1/2 hidden -translate-x-1/2 sm:block" style="--d:0;--gx:0px;--gy:-24px;--cepat:3">
            <a href="{{ $adaEws ? '#kondisi' : '#layanan' }}" class="tanda-gulir flex flex-col items-center gap-1 text-sm font-bold text-white/85 hover:text-white focus-visible:outline-white" aria-label="Gulir ke bagian berikutnya">
                <span>Gulir</span>
                <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </section>
@endsection

@section('isi-rapat', true)
@section('header-transparan', true)

@section('isi')
    {{-- ══════════ 1. KONDISI LERENG HARI INI (bagian sendiri, tidak menumpang di foto) ══════════ --}}
    @if ($adaEws)
        <section id="kondisi" aria-labelledby="judul-kondisi" class="scroll-mt-24 pt-4" data-muncul>
            <div class="grid items-center gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
                <div>
                    <p class="kicker">Peringatan dini longsor</p>
                    <h2 id="judul-kondisi" class="mt-2 text-[clamp(1.7rem,4vw,2.5rem)] font-extrabold leading-tight text-navy text-balance">Kondisi lereng <span class="text-biru">hari ini</span></h2>
                    <p class="mt-3 text-lg text-redup">Sensor di lereng mengirim data tanah, hujan, dan kemiringan secara berkala. Cek di sini sebelum beraktivitas di kebun.</p>
                    <p class="mt-3 flex items-start gap-2 text-sm text-redup"><x-ikon nama="info" class="mt-0.5 size-4 shrink-0 text-biru" />Alat bantu warga, bukan peringatan resmi BPBD.</p>
                    <x-tombol :href="route($ews['rute'])" class="mt-6 min-h-12 px-6">
                        {{ __('landing.hero.tombol') }} <x-ikon nama="panah" class="size-5" />
                    </x-tombol>
                </div>

                {{-- Kotak status (komponen yang sama dengan dasbor EWS): warna + bentuk ikon + kata.
                     langsung = true memberi id panel-* yang diperbarui oleh landing/status-langsung.js. --}}
                <div id="status-langsung" hidden
                     data-url-data="{{ route('ews.data') }}"
                     data-saran="{{ json_encode($saranStatus + ['kosong' => __('landing.status.belum_ada')], JSON_UNESCAPED_UNICODE) }}"
                     data-teks-offline="{{ __('landing.status.alat_mati') }}"
                     data-teks-offline-waspada="{{ __('landing.status.alat_mati_waspada') }}"></div>
                <p id="pengumuman" class="sr-only" aria-live="polite"></p>
                <x-kartu-status
                    :langsung="true"
                    :warna="$warna"
                    :status="$status['status'] ?? null"
                    :saran="$status ? $saranStatus[$warna] : __('landing.status.belum_ada')"
                    :lokasi="$status['lokasi'] ?? null"
                    :pemicu="$status['pemicu'] ?? null"
                    :waktu="$status['waktu'] ?? null"
                    :jumlah-wilayah="$status['jumlah_wilayah'] ?? null"
                    :jumlah-offline="$status['jumlah_offline'] ?? 0"
                    :label="__('landing.status.judul')"
                    :offline="($status['ada_offline'] ?? false) ? __($status['ada_offline_saat_waspada'] ? 'landing.status.alat_mati_waspada' : 'landing.status.alat_mati') : null"
                    :detail="route($ews['rute']).'#sensor'"
                    :bagikan="route($ews['rute'])" />
            </div>
        </section>
    @endif

    {{-- ══════════ 2. LAYANAN: bento grid, kartu masuk bergantian ══════════ --}}
    <section id="layanan" aria-labelledby="judul-modul" class="latar-pudar mt-6 scroll-mt-16 [--ke:#ffffff] [--pudar:6rem]">
        <div class="mx-auto max-w-2xl text-center" data-muncul>
            <p class="kicker justify-center">Layanan desa</p>
            <h2 id="judul-modul" class="mt-2 text-[clamp(1.7rem,4vw,2.5rem)] font-extrabold leading-tight text-navy">{{ __('landing.modul.judul') }}</h2>
            <p class="mt-2 text-lg text-redup">Semua informasi Desa Lumeneng dalam satu pintu, bisa dibuka dari HP.</p>
        </div>

        <ul class="mt-10 grid auto-rows-[minmax(9.5rem,auto)] grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4" data-stagger>
            @foreach ($modul as $kunci => $m)
                @php
                    $b = $bento[$kunci] ?? ['kelas' => '', 'gambar' => null];
                    $berfoto = (bool) $b['gambar'];
                    $gelap = $b['gelap'] ?? false;
                    $besar = $b['besar'] ?? false;
                @endphp
                <li class="{{ $b['kelas'] }}" style="--i: {{ $loop->index }}">
                    @if ($m['tersedia'])
                        <a href="{{ route($m['rute']) }}" @class([
                            'group relative isolate flex h-full flex-col justify-end overflow-hidden rounded-2xl p-5 transition-[transform,box-shadow] duration-300 motion-safe:hover:-translate-y-1 hover:shadow-angkat sm:p-6',
                            'text-white focus-visible:outline-offset-4' => $berfoto || $gelap,
                            'pola-kontur bg-gradient-to-br from-navy to-biru-tua' => ! $berfoto && $gelap,
                            'border border-garis-halus bg-permukaan shadow-kartu' => ! $berfoto && ! $gelap,
                        ])>
                            @if ($berfoto)
                                <x-gambar-situs :kunci="$b['gambar']" class="absolute inset-0 -z-20 size-full motion-safe:transition-transform motion-safe:duration-700 group-hover:scale-105" />
                                <span class="absolute inset-0 -z-10 bg-gradient-to-t from-navy-tua/95 via-navy/55 to-navy/5" aria-hidden="true"></span>
                            @endif
                            <span @class([
                                'mb-auto flex size-12 items-center justify-center rounded-xl transition-colors duration-300',
                                'kaca-terang text-white' => $berfoto,
                                'bg-white/12 text-aksen-terang' => ! $berfoto && $gelap,
                                'bg-biru-muda text-biru group-hover:bg-biru group-hover:text-white' => ! $berfoto && ! $gelap,
                            ])>
                                <x-ikon :nama="$m['ikon']" class="size-6" />
                            </span>
                            <span class="mt-6 block">
                                <span @class([
                                    'flex items-center gap-2 font-judul font-extrabold leading-snug',
                                    'text-2xl sm:text-3xl' => $besar,
                                    'text-lg' => ! $besar,
                                    'text-navy group-hover:text-biru' => ! $berfoto && ! $gelap,
                                    'teks-di-foto' => $berfoto,
                                ])>
                                    {{ $m['nama'] }}
                                    <x-ikon nama="panah" class="size-4 shrink-0 motion-safe:transition-transform motion-safe:duration-200 group-hover:translate-x-1" />
                                </span>
                                <span @class([
                                    'mt-1 block text-sm',
                                    'line-clamp-2' => ! $besar,
                                    'max-w-md text-base' => $besar,
                                    'text-white/85' => $berfoto || $gelap,
                                    'text-redup' => ! $berfoto && ! $gelap,
                                ])>{{ $m['deskripsi'] }}</span>
                            </span>
                        </a>
                    @else
                        <div class="flex h-full flex-col justify-end rounded-2xl border-2 border-dashed border-garis-halus bg-permukaan/60 p-5 sm:p-6">
                            <span class="mb-auto flex size-12 items-center justify-center rounded-xl bg-kosong-lembut text-redup"><x-ikon :nama="$m['ikon']" class="size-6" /></span>
                            <span class="mt-6 block font-judul text-lg font-extrabold text-redup">{{ $m['nama'] }}</span>
                            <span class="mt-1 inline-flex w-fit items-center gap-1 rounded-full bg-kosong-lembut px-2.5 py-0.5 text-xs font-bold text-redup">
                                <x-ikon nama="jam" class="size-3.5" />{{ __('landing.modul.segera') }}
                            </span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    {{-- ══════════ 3. KABAR DESA: info + pengumuman di kiri, berita utama berfoto di kanan ══════════ --}}
    @if ($beritaTerbaru->isNotEmpty() || $pengumuman->isNotEmpty())
        <section aria-labelledby="judul-kabar" class="mt-12">
            <div @class(['grid gap-10 lg:items-stretch', 'lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]' => $beritaUtama])>
                <div class="flex flex-col" data-muncul>
                    <p class="kicker">Berita</p>
                    <h2 id="judul-kabar" class="mt-2 text-[clamp(1.7rem,4vw,2.5rem)] font-extrabold leading-tight text-navy">Kabar terbaru dari desa</h2>
                    <p class="mt-3 text-lg text-redup">Kegiatan warga, pengumuman penting, dan bacaan bermanfaat langsung dari pemerintah desa.</p>

                    @if ($pengumuman->isNotEmpty())
                        <div class="mt-7 rounded-2xl border border-aksen/20 bg-gradient-to-br from-aksen-lembut to-permukaan p-5">
                            <h3 class="flex items-center gap-2 font-judul text-lg font-extrabold text-aksen-tua">
                                <x-ikon nama="pengumuman" class="size-5" /> Pengumuman
                            </h3>
                            <ol class="mt-3 space-y-1">
                                @foreach ($pengumuman as $p)
                                    <li class="group relative flex items-start gap-3 rounded-xl px-2 py-2.5 transition-colors duration-200 hover:bg-white">
                                        <span class="flex size-12 shrink-0 flex-col items-center justify-center rounded-lg bg-white font-judul leading-none text-aksen-tua shadow-sm" aria-hidden="true">
                                            <span class="text-base font-extrabold">{{ $p->diterbitkan_pada?->format('d') }}</span>
                                            <span class="mt-0.5 text-[0.7rem] font-bold uppercase">{{ $p->diterbitkan_pada?->translatedFormat('M') }}</span>
                                        </span>
                                        <span class="min-w-0">
                                            <a href="{{ route('berita.show', $p->slug) }}" class="block font-bold leading-snug after:absolute after:inset-0 group-hover:text-biru group-hover:underline">{{ $p->judul }}</a>
                                            <time class="text-sm text-redup" datetime="{{ $p->diterbitkan_pada?->toDateString() }}">{{ $p->diterbitkan_pada?->format('d-m-Y') }}</time>
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    {{-- Di layar lebar tombol turun ke dasar kolom, sejajar garis bawah kotak berita dan artikel di sebelahnya. --}}
                    <div class="mt-6 flex flex-wrap gap-3 lg:mt-auto lg:pt-6">
                        <x-tombol :href="route('berita.index')">Semua berita <x-ikon nama="panah" class="size-5" /></x-tombol>
                        @if ($pengumuman->isNotEmpty())
                            <x-tombol varian="kedua" :href="route('berita.index', ['jenis' => 'pengumuman'])">Semua pengumuman</x-tombol>
                        @endif
                    </div>
                </div>

                @if ($beritaUtama)
                    <div data-muncul>
                        {{-- Berita utama: judul di atas foto dengan gradasi gelap agar terbaca. --}}
                        <article class="group relative isolate flex min-h-[22rem] flex-col justify-end overflow-hidden rounded-3xl bg-navy p-6 text-white shadow-angkat sm:min-h-[26rem] sm:p-8">
                            @if ($beritaUtama->urlGambar())
                                <img src="{{ $beritaUtama->urlGambar() }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 -z-20 size-full object-cover motion-safe:transition-transform motion-safe:duration-700 group-hover:scale-105">
                            @else
                                <x-gambar-situs kunci="berita.hero" class="absolute inset-0 -z-20 size-full" />
                            @endif
                            <span class="absolute inset-0 -z-10 bg-gradient-to-t from-navy-tua via-navy-tua/60 to-transparent" aria-hidden="true"></span>
                            <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                @include('profil.partials.jenis', ['berita' => $beritaUtama])
                                <time datetime="{{ $beritaUtama->diterbitkan_pada?->toDateString() }}" class="teks-di-foto text-white/90">{{ $beritaUtama->diterbitkan_pada?->format('d-m-Y') }}</time>
                            </p>
                            <h3 class="teks-di-foto mt-3 text-2xl font-extrabold leading-tight text-balance sm:text-3xl">
                                <a href="{{ route('berita.show', $beritaUtama->slug) }}" class="after:absolute after:inset-0 focus-visible:outline-white">{{ $beritaUtama->judul }}</a>
                            </h3>
                            <p class="teks-di-foto mt-2 line-clamp-2 max-w-xl text-white/90">{{ $beritaUtama->ringkasan }}</p>
                            <span class="mt-4 inline-flex items-center gap-1.5 font-bold text-aksen-terang" aria-hidden="true">Baca selengkapnya <x-ikon nama="panah" class="size-4 motion-safe:transition-transform group-hover:translate-x-1" /></span>
                        </article>

                        @if ($beritaTerbaru->count() > 1)
                            <ul class="mt-4 grid gap-4 sm:grid-cols-2" data-stagger>
                                @foreach ($beritaTerbaru->skip(1) as $b)
                                    <li class="group relative flex gap-3 rounded-2xl border border-garis-halus bg-permukaan p-3 shadow-kartu transition-shadow duration-300 hover:shadow-angkat" style="--i: {{ $loop->index }}">
                                        <span class="relative size-20 shrink-0 overflow-hidden rounded-xl bg-biru-lembut">
                                            @if ($b->urlGambar())
                                                <img src="{{ $b->urlGambar() }}" alt="" loading="lazy" decoding="async" class="size-full object-cover motion-safe:transition-transform motion-safe:duration-500 group-hover:scale-110">
                                            @else
                                                <span class="flex size-full items-center justify-center text-biru"><x-ikon nama="berita" class="size-7" /></span>
                                            @endif
                                        </span>
                                        <span class="min-w-0">
                                            @include('profil.partials.jenis', ['berita' => $b])
                                            <a href="{{ route('berita.show', $b->slug) }}" class="mt-1 line-clamp-2 block font-judul font-bold leading-snug after:absolute after:inset-0 group-hover:text-biru">{{ $b->judul }}</a>
                                            <time class="text-sm text-redup" datetime="{{ $b->diterbitkan_pada?->toDateString() }}">{{ $b->diterbitkan_pada?->format('d-m-Y') }}</time>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ══════════ 4. DESA DALAM ANGKA: pita navy berlatar foto, tepi atas-bawah memudar ══════════ --}}
    @if ($statistik)
        <section aria-labelledby="judul-statistik" class="latar-pudar mt-8 text-white [--ke-tengah:var(--color-biru-tua)]">
            <div class="lapis-pudar" aria-hidden="true">
                <x-gambar-situs kunci="beranda.angka" class="size-full opacity-35" />
                <span class="pola-kontur absolute inset-0"></span>
            </div>
            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                <div data-muncul>
                    <p class="kicker kicker--terang">Sekilas desa</p>
                    <h2 id="judul-statistik" class="mt-2 text-[clamp(1.7rem,4vw,2.5rem)] font-extrabold leading-tight">Desa dalam angka</h2>
                    <p class="mt-3 text-lg text-white/85">Data kependudukan dan wilayah Desa Lumeneng dari pemerintah desa.</p>
                    <a href="{{ route('profil.index') }}" class="mt-5 inline-flex min-h-11 items-center gap-1.5 font-bold text-aksen-terang hover:text-white hover:underline focus-visible:outline-white">Profil lengkap desa <x-ikon nama="panah" class="size-4" /></a>
                </div>
                <dl class="grid grid-cols-2 gap-3 sm:gap-4" data-stagger>
                    @foreach ($statistik as $angka)
                        <div class="kaca rounded-2xl p-5 transition-colors duration-300 hover:bg-white/15 sm:p-6" style="--i: {{ $loop->index }}">
                            <dt class="text-sm font-bold text-white/80">{{ $angka['label'] }}</dt>
                            <dd class="mt-2 flex flex-wrap items-baseline gap-x-2">
                                <span class="font-judul text-[clamp(2rem,6vw,3rem)] font-extrabold leading-none tabular-nums" data-hitung>{{ $angka['nilai'] }}</span>
                                <span class="font-bold text-aksen-terang">{{ $angka['satuan'] }}</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    {{-- ══════════ 4b. BENTANG ALAM 3D: gambar tiga dimensi desa yang bisa diputar (resources/js/landing/bentang-3d.js) ══════════ --}}
    <section aria-labelledby="judul-bentang" class="pola-kontur relative isolate mt-16 overflow-hidden rounded-3xl bg-gradient-to-br from-navy-tua via-navy to-biru-tua text-white shadow-angkat">
        <div class="grid items-center gap-6 p-6 sm:p-10 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.4fr)] lg:gap-10">
            <div data-muncul>
                <p class="kicker kicker--terang">Kenali desa kita</p>
                <h2 id="judul-bentang" class="mt-2 text-[clamp(1.7rem,4vw,2.5rem)] font-extrabold leading-tight text-balance">Lihat Lumeneng dari atas, <span class="text-aksen-terang">putar sesukamu</span></h2>
                <p class="mt-3 text-lg text-white/85">Desa kita berdiri di lereng perbukitan. Geser gambar untuk memutarnya, lalu perbesar untuk melihat letak dusun dan dukuh.</p>
                <ul class="mt-4 space-y-2 text-white/85">
                    <li class="flex items-start gap-2"><x-ikon nama="info" class="mt-0.5 size-5 shrink-0 text-aksen-terang" /><span><strong class="text-white">Geser</strong> dengan jari atau mouse untuk memutar.</span></li>
                    <li class="flex items-start gap-2"><x-ikon nama="perbesar" class="mt-0.5 size-5 shrink-0 text-aksen-terang" /><span><strong class="text-white">Cubit</strong> dua jari, atau pakai tombol + dan −, untuk memperbesar.</span></li>
                </ul>
                @if ($adaEws)
                    <x-tombol varian="terang" :href="route($ews['rute']).'#kerawanan'" class="mt-6 min-h-12 px-6">
                        <x-ikon nama="peta" class="size-5" /><span>Lihat peta rawan longsor</span>
                    </x-tombol>
                @endif
            </div>

            {{-- Tanpa JavaScript, ini tetap gambar biasa; tombol kendali baru muncul bila skrip siap. --}}
            <div data-bentang3d data-muncul>
                <div data-b3d-panggung tabindex="0" role="group"
                     aria-label="{{ $bentangAlam['judul'] }}. Gunakan tombol panah untuk memutar, plus dan minus untuk memperbesar."
                     class="relative aspect-[1.6/1] touch-pan-y cursor-grab select-none overflow-hidden rounded-2xl bg-[radial-gradient(ellipse_at_50%_62%,rgb(59_130_246/0.28),transparent_68%)] [perspective:1100px] active:cursor-grabbing focus-visible:outline-aksen-terang">
                    {{-- Bayangan di bawah model supaya terkesan melayang di atas lantai. --}}
                    <span class="pointer-events-none absolute bottom-[6%] left-1/2 h-[7%] w-[62%] -translate-x-1/2 rounded-full bg-black/55 blur-xl" aria-hidden="true"></span>
                    <div data-b3d-model class="absolute inset-0 [transform-style:preserve-3d] will-change-transform">
                        <img src="{{ site_image('beranda.peta-3d') }}" alt="{{ site_image_alt('beranda.peta-3d') }}" loading="lazy" decoding="async" draggable="false"
                             class="pointer-events-none size-full object-contain drop-shadow-[0_18px_28px_rgb(0_0_0/0.45)]">
                    </div>
                    <p data-b3d-petunjuk class="kaca pointer-events-none absolute left-3 top-3 whitespace-nowrap rounded-full px-4 py-1.5 text-sm font-bold text-white transition-opacity duration-500">
                        Geser untuk memutar
                    </p>
                </div>

                <div data-b3d-kendali hidden class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    @php
                        $tombol3d = [
                            ['kiri', 'Putar ke kiri', 'm15 5-7 7 7 7'],
                            ['kanan', 'Putar ke kanan', 'm9 5 7 7-7 7'],
                            ['kecil', 'Perkecil', 'M5 12h14'],
                            ['besar', 'Perbesar', 'M12 5v14M5 12h14'],
                            ['ulang', 'Atur ulang', 'M4 12a8 8 0 1 0 2.5-5.8M4 4v5h5'],
                        ];
                    @endphp
                    @foreach ($tombol3d as [$aksi, $nama, $garis])
                        <button type="button" data-aksi="{{ $aksi }}" title="{{ $nama }}"
                                class="kaca inline-flex min-h-11 min-w-11 items-center justify-center gap-2 rounded-full px-3 font-bold text-white transition-colors duration-200 hover:bg-white/25 focus-visible:outline-white disabled:opacity-40 sm:px-4">
                            <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $garis }}" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span class="{{ in_array($aksi, ['kiri', 'kanan'], true) ? 'sr-only' : 'sr-only sm:not-sr-only' }}">{{ $nama }}</span>
                        </button>
                    @endforeach
                </div>
                <p data-b3d-info class="sr-only" aria-live="polite"></p>

                <p class="mt-3 text-center text-sm text-white/70">{{ $bentangAlam['penjelasan'] }} Gambar dari Kajian Pemetaan Bencana Desa Lumeneng 2024; bukan peta untuk navigasi.</p>
            </div>
        </div>
    </section>

    {{-- ══════════ 5. SIAGA BENCANA: foto di kiri, sensor + nomor darurat di kanan ══════════ --}}
    <section aria-labelledby="judul-siaga" class="mt-16 grid items-center gap-10 lg:grid-cols-2">
        <div class="relative" data-muncul>
            <x-gambar-situs kunci="ews.hero" class="aspect-[4/3] w-full rounded-3xl shadow-angkat lg:aspect-[4/5]" />
            <div class="pointer-events-none absolute inset-0 rounded-3xl bg-gradient-to-t from-navy-tua/75 via-transparent to-transparent" aria-hidden="true"></div>
            <p class="kaca absolute bottom-5 left-5 right-5 rounded-2xl px-4 py-3 text-white">
                <span class="block text-xs font-bold uppercase tracking-[0.12em] text-aksen-terang">Siaga bersama</span>
                <span class="block font-judul font-extrabold">Karang Taruna Giri · 25 kader tanggap bencana</span>
            </p>
        </div>

        <div data-muncul>
            <p class="kicker">Siaga bencana</p>
            <h2 id="judul-siaga" class="mt-2 text-[clamp(1.6rem,3.6vw,2.3rem)] font-extrabold leading-tight text-navy text-balance">{{ __('landing.hero.judul') }}</h2>
            <p class="mt-3 text-lg text-redup">{{ __('landing.hero.tujuan') }}</p>

            @if ($adaEws)
                <ul class="mt-6 grid gap-3 sm:grid-cols-2" data-stagger>
                    @foreach ($sensorBeranda as [$ikon, $nama, $isi])
                        <li class="flex gap-3 rounded-xl border border-garis-halus bg-permukaan p-3 transition-colors duration-200 hover:border-biru/40" style="--i: {{ $loop->index }}">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-biru-muda text-biru"><x-ikon :nama="$ikon" class="size-5" /></span>
                            <span><span class="block font-bold leading-snug">{{ $nama }}</span><span class="block text-sm text-redup">{{ $isi }}</span></span>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Biru keabuan padat: beda dari latar krem dan dari kartu sensor putih di atasnya; judul merah tetap menandai darurat. --}}
            <div id="judul-kontak" class="mt-6 scroll-mt-24 rounded-2xl border border-biru/15 bg-gradient-to-br from-[#edf2fa] to-[#dfe8f5] p-5 shadow-kartu">
                <h3 class="flex items-center gap-2 font-judul text-lg font-extrabold text-awas"><x-ikon nama="telepon" class="size-5" />{{ __('landing.kontak.judul') }}</h3>
                <p class="mt-2 flex items-start gap-2 text-sm text-redup"><x-ikon nama="lokasi" class="mt-0.5 size-4 shrink-0" />{{ __('landing.kontak.lokasi') }}</p>
                <p class="mt-3 text-sm font-bold text-awas">{{ __('landing.kontak.darurat_judul') }}</p>
                @if (__('landing.kontak.darurat_nomor'))
                    <a href="tel:{{ __('landing.kontak.darurat_nomor') }}" class="mt-2 flex min-h-14 items-center justify-center gap-3 rounded-xl bg-awas px-4 text-2xl font-bold text-white shadow-md hover:bg-awas-tua">
                        <x-ikon nama="telepon" class="size-6" />{{ __('landing.kontak.darurat_nomor') }}
                    </a>
                @else
                    <p class="mt-2 rounded-xl bg-white px-4 py-3 text-sm shadow-sm">{{ __('landing.kontak.darurat_kosong') }}</p>
                @endif
                <ul class="mt-4 grid gap-2 border-t border-biru/15 pt-4 text-sm sm:grid-cols-3">
                    <li class="flex gap-2"><x-ikon nama="hujan" class="size-5 shrink-0 text-biru" />Menjauh dari lereng saat hujan deras.</li>
                    <li class="flex gap-2"><x-ikon nama="telepon" class="size-5 shrink-0 text-biru" />Simpan nomor kader di HP.</li>
                    <li class="flex gap-2"><x-ikon nama="jam" class="size-5 shrink-0 text-biru" />Cek status pagi dan malam.</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- ══════════ 6. GALERI: susunan mozaik, foto bergiliran ══════════ --}}
    @if ($fotoTerbaru->isNotEmpty())
        <section aria-labelledby="judul-galeri" class="latar-pudar mt-8 [--ke:var(--color-krem-tua)] [--pudar:6rem]">
            <div class="flex flex-wrap items-end justify-between gap-3" data-muncul>
                <div>
                    <p class="kicker">Potret Desa Lumeneng</p>
                    <h2 id="judul-galeri" class="mt-2 text-[clamp(1.7rem,4vw,2.5rem)] font-extrabold leading-tight text-navy">Galeri desa</h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($fotoGilir->isNotEmpty())
                        {{-- Foto berganti sendiri, jadi wajib ada tombol untuk menghentikannya (WCAG 2.2.2). --}}
                        <button type="button" data-gilir-jeda aria-pressed="false" hidden
                                class="inline-flex min-h-11 items-center gap-2 rounded-[0.7rem] border border-garis bg-permukaan px-4 font-judul font-bold transition-colors duration-200 hover:border-biru hover:bg-biru-lembut aria-pressed:border-biru aria-pressed:bg-biru aria-pressed:text-white">
                            <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path data-ikon-jeda d="M9 5v14M15 5v14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path data-ikon-putar d="M8 5v14l11-7Z" fill="currentColor" hidden/></svg>
                            <span data-teks>Jeda pergantian foto</span>
                        </button>
                    @endif
                    <x-tombol varian="kedua" :href="route('galeri.index')">Lihat semua foto <x-ikon nama="panah" class="size-5" /></x-tombol>
                </div>
            </div>
            <ul data-galeri data-stagger @if ($fotoGilir->isNotEmpty()) data-gilir @endif class="mt-8 grid auto-rows-[9rem] grid-cols-2 gap-3 sm:auto-rows-[11rem] sm:grid-cols-4 sm:gap-4">
                @foreach ($fotoTerbaru as $foto)
                    <li @class([
                        'relative [&>a]:h-full [&_img]:!aspect-auto [&_img]:h-full',
                        'col-span-2 row-span-2' => $loop->index === 0,
                    ]) style="--i: {{ $loop->index }}">@include('profil.partials.kotak-foto')</li>
                @endforeach
            </ul>
            @if ($fotoGilir->isNotEmpty())
                {{-- Foto cadangan untuk digilir (resources/js/landing/gilir.js). Isi <template> tidak dimuat browser sampai dipakai. --}}
                <template data-gilir-cadangan>
                    @foreach ($fotoGilir as $foto)
                        @include('profil.partials.kotak-foto')
                    @endforeach
                </template>
                @push('skrip')
                    @vite('resources/js/landing/gilir.js')
                @endpush
            @endif
        </section>
    @endif

    {{-- ══════════ 7. PRODUK WARGA: foto kiri, teks kanan ══════════ --}}
    @if (($toko['tersedia'] ?? false) || ($limbah['tersedia'] ?? false))
        <section aria-labelledby="judul-produk" class="mt-16 grid items-center gap-10 lg:grid-cols-2">
            <div class="relative" data-muncul>
                <x-gambar-situs kunci="beranda.produk" class="aspect-[4/3] w-full rounded-3xl shadow-angkat" />
                <span class="absolute left-5 top-5 inline-flex items-center gap-2 rounded-full bg-aksen px-4 py-2 font-judul text-sm font-extrabold text-white shadow-lg" aria-hidden="true">
                    <x-ikon nama="daun" class="size-4" /> Ekonomi sirkular
                </span>
            </div>
            <div data-muncul>
                <p class="kicker">Produk warga</p>
                <h2 id="judul-produk" class="mt-2 text-[clamp(1.7rem,4vw,2.5rem)] font-extrabold leading-tight text-navy text-balance">Dari kebun warga, <span class="text-biru">langsung ke rumah Anda</span></h2>
                <p class="mt-3 text-lg text-redup">Kopi, baglog dari cangkang kopi, dan jamur tiram buatan warga. Limbah kopi tidak dibuang, tetapi diolah lagi menjadi media tanam jamur.</p>
                <ol class="mt-6 grid gap-3 sm:grid-cols-3" data-stagger>
                    @foreach (['Panen kopi warga', 'Cangkang jadi baglog', 'Jamur sampai pembeli'] as $teks)
                        <li class="flex items-center gap-3 rounded-xl bg-permukaan p-3 shadow-kartu sm:flex-col sm:items-start" style="--i: {{ $loop->index }}">
                            <span class="font-judul text-2xl font-extrabold text-aksen" aria-hidden="true">{{ $loop->iteration }}</span>
                            <span class="font-bold leading-snug">{{ $teks }}</span>
                        </li>
                    @endforeach
                </ol>
                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($toko['tersedia'] ?? false)
                        <x-tombol :href="route($toko['rute'])" class="min-h-12 px-6"><x-ikon nama="toko" class="size-5" /><span>Belanja di Toko Desa</span></x-tombol>
                    @endif
                    @if ($limbah['tersedia'] ?? false)
                        <x-tombol varian="kedua" :href="route($limbah['rute'])" class="min-h-12 px-6"><x-ikon nama="tanah" class="size-5" /><span>{{ $limbah['nama'] }}</span></x-tombol>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ 8. TENTANG PROGRAM: teks kiri, foto kanan ══════════ --}}
    <section aria-labelledby="judul-tentang" class="mt-20 grid items-center gap-10 lg:grid-cols-2">
        <div class="lg:order-2" data-muncul>
            <x-gambar-situs kunci="beranda.tentang" class="aspect-[4/3] w-full rounded-3xl shadow-angkat" />
        </div>
        <div data-muncul>
            <p class="kicker">{{ __('landing.hero.program') }}</p>
            <h2 id="judul-tentang" class="mt-2 text-[clamp(1.7rem,4vw,2.5rem)] font-extrabold leading-tight text-navy">{{ __('landing.tentang.judul') }}</h2>
            @foreach (__('landing.tentang.isi') as $paragraf)
                <p class="mt-3 text-redup">{{ $paragraf }}</p>
            @endforeach
            <h3 class="mt-6 font-extrabold text-navy">{{ __('landing.mitra.judul') }}</h3>
            <ul class="mt-3 grid gap-3 sm:grid-cols-2" data-stagger>
                @foreach (__('landing.mitra.daftar') as $mitra)
                    <li class="flex items-start gap-3 rounded-xl border border-garis-halus bg-permukaan p-3 transition-shadow duration-300 hover:shadow-kartu" style="--i: {{ $loop->index }}">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-biru-muda font-judul text-sm font-extrabold text-biru" aria-hidden="true">{{ mb_substr($mitra['nama'], 0, 2) }}</span>
                        <span>
                            <span class="block font-bold leading-snug">{{ $mitra['nama'] }}</span>
                            @if ($mitra['peran'])
                                <span class="block text-sm text-redup">{{ $mitra['peran'] }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endsection
