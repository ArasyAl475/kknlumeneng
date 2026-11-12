{{-- Sampul Profil Desa (dipakai di @section('hero')): foto lebar dari slot Desain Web "profil.hero".
     Variabel: $profil (ProfilDesa), $judul (opsional, untuk subhalaman). Tab antarbagian ada di profil.partials.tab. --}}
<section class="relative isolate overflow-hidden bg-navy text-white">
    <x-gambar-situs kunci="profil.hero" :prioritas="true" class="absolute inset-0 -z-20 size-full" />
    <div class="lapis-hero absolute inset-0 -z-10" aria-hidden="true"></div>

    <div class="teks-di-foto mx-auto max-w-6xl px-4 pb-24 pt-28 sm:pb-28 sm:pt-36">
        <nav aria-label="Jejak halaman" class="text-sm text-white/80">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="{{ route('landing') }}" class="hover:text-white hover:underline focus-visible:outline-white">Beranda</a></li>
                <li aria-hidden="true">/</li>
                @if (isset($judul))
                    <li><a href="{{ route('profil.index') }}" class="hover:text-white hover:underline focus-visible:outline-white">Profil Desa</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="font-bold text-white" aria-current="page">{{ $judul }}</li>
                @else
                    <li class="font-bold text-white" aria-current="page">Profil Desa</li>
                @endif
            </ol>
        </nav>
        <p class="kicker kicker--terang mt-6">{{ isset($judul) ? 'Profil '.$profil->namaDesa() : 'Profil Desa' }}</p>
        <h1 class="mt-2 max-w-4xl text-[clamp(2.2rem,7vw,4.25rem)] font-extrabold leading-[1.05] tracking-[-0.035em] text-balance">{{ $judul ?? $profil->namaDesa() }}</h1>
        @if (! isset($judul) && $profil->ada('tagline'))
            <p class="mt-3 max-w-2xl text-lg text-white/90 sm:text-xl">{{ $profil->nilai('tagline') }}</p>
        @endif
        @if ($profil->lokasi())
            <p class="kaca-terang mt-5 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold">
                <x-ikon nama="lokasi" class="size-4 text-aksen-terang" />
                <span>{{ $profil->lokasi() }}</span>
            </p>
        @endif
    </div>
</section>
