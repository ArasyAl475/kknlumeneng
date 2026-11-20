{{-- Footer publik bersama (layouts/app dan toko/kerangka). Disclaimer peringatan dini wajib selalu tampil. --}}
@php
    $menuKaki = \App\Support\Modul::tersedia();
    // Footer tidak boleh membuat halaman gagal tampil hanya karena data profil belum bisa dibaca.
    $profilKaki = rescue(fn () => \App\Services\Profil\ProfilDesa::muat(), null, false);
@endphp
<footer class="pola-kontur mt-auto bg-navy text-white">
    {{-- Kelas di footer sengaja memakai yang sudah ada di public/build server, agar cukup git pull. --}}
    <div class="mx-auto grid max-w-6xl gap-10 px-4 pt-10 pb-10 sm:grid-cols-2 lg:grid-cols-3">
        <div class="sm:col-span-2 lg:col-span-1">
            <p class="flex items-center gap-3 font-judul text-xl font-extrabold">
                <x-logo-desa class="size-10" />
                Desa Lumeneng
            </p>
            <p class="mt-3 max-w-md leading-relaxed text-white/75">Kec. Paninggaran, Kab. Pekalongan, Jawa Tengah. Desa di lereng perbukitan dengan kebun kopi dan jamur tiram warga.</p>
        </div>

        <nav aria-labelledby="judul-kaki-menu">
            <h2 id="judul-kaki-menu" class="font-judul text-sm font-extrabold uppercase tracking-[0.12em] text-aksen-terang">Jelajahi</h2>
            {{-- Dua kolom agar delapan menu tidak memanjang ke bawah. --}}
            <ul class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1">
                <li><a href="{{ route('landing') }}" class="inline-flex min-h-9 items-center text-white/85 hover:text-white hover:underline focus-visible:outline-white">Beranda</a></li>
                @foreach ($menuKaki as $m)
                    <li><a href="{{ route($m['rute']) }}" class="inline-flex min-h-9 items-center text-white/85 hover:text-white hover:underline focus-visible:outline-white">{{ $m['nama'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div>
            <h2 class="font-judul text-sm font-extrabold uppercase tracking-[0.12em] text-aksen-terang">Kantor desa</h2>
            <ul class="mt-3 space-y-3 text-white/85">
                <li class="flex items-start gap-2.5"><x-ikon nama="lokasi" class="mt-0.5 size-5 shrink-0 text-langit" /><span>{{ $profilKaki?->nilai('alamat_kantor') ?: 'Desa Lumeneng, Kec. Paninggaran, Kab. Pekalongan' }}</span></li>
                @if ($profilKaki?->teleponTautan())
                    <li><a href="tel:{{ $profilKaki->teleponTautan() }}" class="flex min-h-9 items-center gap-2.5 hover:text-white hover:underline focus-visible:outline-white"><x-ikon nama="telepon" class="size-5 shrink-0 text-langit" />{{ $profilKaki->nilai('telepon') }}</a></li>
                @endif
                @if ($profilKaki?->ada('email'))
                    @php($email = $profilKaki->nilai('email'))
                    {{-- <wbr>: bila kolom sempit, email patah setelah "@", bukan di tengah kata. --}}
                    <li><a href="mailto:{{ $email }}" class="flex min-h-9 items-center gap-2.5 hover:text-white hover:underline focus-visible:outline-white"><x-ikon nama="surel" class="size-5 shrink-0 text-langit" /><span>{{ \Illuminate\Support\Str::before($email, '@') }}@<wbr>{{ \Illuminate\Support\Str::after($email, '@') }}</span></a></li>
                @endif
            </ul>
        </div>
    </div>

    {{-- Disclaimer wajib: satu pita mendatar, tingginya mengikuti isi (tidak lagi memanjang setinggi kolom menu). --}}
    <div class="mx-auto max-w-6xl px-4 pb-10">
        <div class="flex items-start gap-3 rounded-2xl border border-white/15 bg-white/5 p-4 sm:items-center sm:gap-4 sm:p-5">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10">
                <x-ikon nama="waspada" class="size-5 text-waspada" />
            </span>
            <div>
                <h2 class="font-judul text-base font-extrabold">Catatan penting</h2>
                <p class="mt-0.5 text-sm leading-relaxed text-white/85">
                    Informasi peringatan dini di situs ini adalah <strong class="text-white">alat bantu</strong>, bukan peringatan resmi BPBD.
                    Saat kondisi berbahaya, segera menjauh dari lereng dan hubungi perangkat desa.
                </p>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10 bg-navy-tua/60">
        <div class="mx-auto max-w-6xl px-4 py-4 text-sm text-white/65">
            <p>&copy; {{ now()->year }} Desa Lumeneng · Program KKN / Aksara Peduli Universitas Ivet</p>
        </div>
    </div>
</footer>

<x-ke-atas />
