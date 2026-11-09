{{-- Footer publik bersama (layouts/app dan toko/kerangka). Disclaimer peringatan dini wajib selalu tampil. --}}
@php
    $menuKaki = \App\Support\Modul::tersedia();
    // Footer tidak boleh membuat halaman gagal tampil hanya karena data profil belum bisa dibaca.
    $profilKaki = rescue(fn () => \App\Services\Profil\ProfilDesa::muat(), null, false);
@endphp
<footer class="pola-kontur mt-auto bg-navy text-white">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1.3fr]">
        <div>
            <p class="flex items-center gap-3 font-judul text-xl font-extrabold">
                <span class="flex size-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15">
                    <svg class="size-6 text-aksen-terang" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 19.5 9 9l4 6 2.5-3.5 6 8Z" /><circle cx="17" cy="6" r="2.2" /></svg>
                </span>
                Desa Lumeneng
            </p>
            <p class="mt-3 text-white/75">Kec. Paninggaran, Kab. Pekalongan, Jawa Tengah. Desa di lereng perbukitan dengan kebun kopi dan jamur tiram warga.</p>
        </div>

        <nav aria-labelledby="judul-kaki-menu">
            <h2 id="judul-kaki-menu" class="font-judul text-sm font-extrabold uppercase tracking-[0.12em] text-aksen-terang">Jelajahi</h2>
            <ul class="mt-3 space-y-1">
                <li><a href="{{ route('landing') }}" class="inline-flex min-h-9 items-center text-white/85 hover:text-white hover:underline focus-visible:outline-white">Beranda</a></li>
                @foreach ($menuKaki as $m)
                    <li><a href="{{ route($m['rute']) }}" class="inline-flex min-h-9 items-center text-white/85 hover:text-white hover:underline focus-visible:outline-white">{{ $m['nama'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div>
            <h2 class="font-judul text-sm font-extrabold uppercase tracking-[0.12em] text-aksen-terang">Kantor desa</h2>
            <ul class="mt-3 space-y-3 text-white/85">
                <li class="flex items-start gap-2.5"><x-ikon nama="lokasi" class="mt-0.5 size-5 text-langit" /><span>{{ $profilKaki?->nilai('alamat_kantor') ?: 'Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan' }}</span></li>
                @if ($profilKaki?->teleponTautan())
                    <li><a href="tel:{{ $profilKaki->teleponTautan() }}" class="flex min-h-9 items-center gap-2.5 hover:text-white hover:underline focus-visible:outline-white"><x-ikon nama="telepon" class="size-5 text-langit" />{{ $profilKaki->nilai('telepon') }}</a></li>
                @endif
                @if ($profilKaki?->ada('email'))
                    <li><a href="mailto:{{ $profilKaki->nilai('email') }}" class="flex min-h-9 items-center gap-2.5 break-all hover:text-white hover:underline focus-visible:outline-white"><x-ikon nama="surel" class="size-5 shrink-0 text-langit" />{{ $profilKaki->nilai('email') }}</a></li>
                @endif
            </ul>
        </div>

        <div class="rounded-2xl border border-white/15 bg-white/5 p-5">
            <h2 class="flex items-center gap-2 font-judul text-base font-extrabold">
                <x-ikon nama="waspada" class="size-5 text-waspada" /> Catatan penting
            </h2>
            <p class="mt-2 text-sm text-white/85">
                Informasi peringatan dini di situs ini adalah <strong class="text-white">alat bantu</strong>, bukan peringatan resmi BPBD.
                Saat kondisi berbahaya, segera menjauh dari lereng dan hubungi perangkat desa.
            </p>
        </div>
    </div>

    <div class="border-t border-white/10 bg-navy-tua/60">
        <div class="mx-auto max-w-6xl px-4 py-4 text-sm text-white/65">
            <p>&copy; {{ now()->year }} Desa Lumeneng · Program KKN / Aksara Peduli Universitas Ivet</p>
        </div>
    </div>
</footer>

<x-ke-atas />
