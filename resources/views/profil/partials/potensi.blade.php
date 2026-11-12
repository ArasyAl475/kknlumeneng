{{-- Potensi & penghasil desa. Variabel: $potensi (koleksi Potensi dengan relasi fotos, sudah urut).
     Tata letak editorial tanpa kartu: satu sisi kolase foto, sisi lain penjelasan, bergantian kiri-kanan.
     Kolase = foto berbeda bentuk (tegak, mendatar, kotak, jalur panjang) yang saling mengisi sebuah kotak
     tanpa celah (App\Services\Profil\SusunanKolase). Foto pertama paling besar. Diketuk = penampil foto layar penuh
     yang bisa digeser (resources/js/profil/galeri.js), termasuk foto di atas 6 yang tidak digambar di kolase.
     Foto-foto ini sama dengan foto di menu Galeri (tabel profil_galeri). --}}
@php use App\Services\Profil\SusunanKolase; @endphp
@if ($potensi->isNotEmpty())
    @push('skrip')
        @vite('resources/js/profil/galeri.js')
    @endpush
    <section id="potensi" aria-labelledby="judul-potensi" class="mt-16 scroll-mt-20">
        <div class="mb-6 text-center" data-muncul>
            <p class="kicker justify-center">Sumber daya desa</p>
            <h2 id="judul-potensi" class="mt-2 text-[clamp(1.8rem,4.5vw,2.8rem)] font-extrabold text-navy">Potensi &amp; penghasil desa</h2>
            <p class="mx-auto mt-2 max-w-2xl text-redup">Daerah apa saja yang menghidupi warga {{ $profil->namaDesa() }}, penghasilnya, dan letaknya.</p>
        </div>

        <div class="divide-y divide-garis-halus border-t border-garis-halus">
            @foreach ($potensi as $p)
                @php
                    $jumlahFoto = $p->fotos->count();
                    $tampil = $p->fotos->take(SusunanKolase::MAKS_TAMPIL)->values();
                    $sisa = $p->fotos->slice(SusunanKolase::MAKS_TAMPIL)->values();
                    $area = $jumlahFoto > 0 ? SusunanKolase::area($jumlahFoto) : [];
                @endphp
                <article class="grid items-center gap-8 py-10 lg:grid-cols-2 lg:gap-14 lg:py-14" aria-labelledby="potensi-{{ $p->id }}" data-muncul>
                    {{-- Kolase foto. Baris genap menaruhnya di kanan (lg:order-2). --}}
                    <div @class(['lg:order-2' => $loop->even])>
                        @if ($jumlahFoto > 0)
                            <div data-galeri role="group" aria-label="Foto {{ $p->nama }}" class="grid aspect-square grid-cols-6 grid-rows-6 gap-2 sm:aspect-[5/4] sm:gap-3">
                                @foreach ($tampil as $i => $foto)
                                    @php $url = $foto->urlGambar(); @endphp
                                    <a href="{{ $url }}" data-foto data-besar="{{ $url }}" data-judul="{{ $foto->judul ?: $p->nama }}" data-keterangan="{{ $foto->keterangan }}"
                                       style="grid-area: {{ $area[$i] }}" aria-label="Perbesar foto {{ $i + 1 }} dari {{ $jumlahFoto }}: {{ $p->nama }}"
                                       class="group relative block overflow-hidden rounded-2xl bg-biru-lembut shadow-kartu focus-visible:outline-offset-4">
                                        <img src="{{ $url }}" alt="" loading="lazy" decoding="async"
                                             class="absolute inset-0 size-full object-cover motion-safe:transition-transform motion-safe:duration-500 group-hover:scale-105">

                                        {{-- Luas di foto terbesar, sebagai label kaca seperti kolase pada umumnya. --}}
                                        @if ($loop->first && $p->luas)
                                            <span class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/50 to-transparent" aria-hidden="true"></span>
                                            <span class="kaca absolute bottom-2.5 left-2.5 flex max-w-[calc(100%-1.25rem)] items-center gap-1.5 rounded-xl px-3 py-1.5 text-sm font-bold leading-snug text-white sm:text-base">
                                                <x-ikon nama="lokasi" class="size-4 shrink-0" /><span><span class="sr-only">Luas: </span>{{ $p->luas }}</span>
                                            </span>
                                        @endif

                                        {{-- Masih ada foto lain: tile terakhir mengajak membukanya. --}}
                                        @if ($loop->last && $sisa->isNotEmpty())
                                            <span class="absolute inset-0 flex items-center justify-center bg-navy/60 font-judul text-2xl font-extrabold text-white" aria-hidden="true">+{{ $sisa->count() }}</span>
                                        @endif
                                    </a>
                                @endforeach

                                {{-- Foto di atas 6 tidak digambar, tetapi ikut digeser di penampil foto. --}}
                                @foreach ($sisa as $foto)
                                    @php $url = $foto->urlGambar(); @endphp
                                    <a href="{{ $url }}" data-foto hidden data-besar="{{ $url }}" data-judul="{{ $foto->judul ?: $p->nama }}" data-keterangan="{{ $foto->keterangan }}">{{ $foto->judul ?: $p->nama }}</a>
                                @endforeach
                            </div>
                        @else
                            <div class="flex aspect-[5/4] items-center justify-center rounded-3xl bg-biru-lembut text-biru/40" aria-hidden="true"><x-ikon :nama="$p->ikonKategori()" class="size-20" /></div>
                        @endif
                    </div>

                    {{-- Penjelasan: tanpa pembungkus kartu, langsung di atas latar halaman. --}}
                    <div class="min-w-0">
                        <p class="inline-flex items-center gap-2 rounded-full bg-biru-muda px-3.5 py-1.5 font-bold text-biru">
                            <x-ikon :nama="$p->ikonKategori()" class="size-5" />{{ $p->labelKategori() }}
                        </p>
                        <h3 id="potensi-{{ $p->id }}" class="mt-3 font-judul text-[clamp(1.9rem,4vw,2.8rem)] font-extrabold leading-tight text-navy text-balance">{{ $p->nama }}</h3>

                        @if ($p->luas && $jumlahFoto === 0)
                            <p class="mt-1 font-judul text-lg font-extrabold text-aksen-tua">{{ $p->luas }}</p>
                        @endif

                        @if ($p->daftarHasil())
                            <div class="mt-4">
                                <p class="text-sm font-bold uppercase tracking-wider text-aksen-tua">Penghasil</p>
                                <ul class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($p->daftarHasil() as $hasil)
                                        <li class="rounded-full bg-aksen-lembut px-3.5 py-1.5 font-bold text-aksen-tua">{{ $hasil }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($p->keterangan)
                            <p class="mt-4 max-w-xl text-lg text-redup">{{ $p->keterangan }}</p>
                        @endif

                        @if ($p->lokasi)
                            <p class="mt-4 flex items-start gap-2 font-bold text-navy">
                                <x-ikon nama="lokasi" class="mt-1 size-5 shrink-0 text-biru" />
                                <span><span class="sr-only">Lokasi: </span>{{ $p->lokasi }}</span>
                            </p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <p class="mt-2 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-center text-sm text-redup">
            @if ($potensi->contains(fn ($p) => $p->fotos->isNotEmpty()) && Route::has('galeri.index'))
                <a href="{{ route('galeri.index', ['jenis' => 'desa']) }}" class="inline-flex min-h-11 items-center gap-1.5 font-bold text-biru hover:underline">Lihat semua foto di Galeri <x-ikon nama="panah" class="size-4" /></a>
            @endif
            {{-- Kredit hanya bila ada foto bawaan dari kajian (disalin seeder ke folder profil/kajian). --}}
            @if ($potensi->flatMap->fotos->contains(fn ($f) => str_starts_with($f->gambar, 'profil/kajian/')))
                <span>Data dan foto awal: Kajian Pemetaan Bencana Desa Lumeneng 2024 (LPBI NU Kab. Pekalongan &amp; Ringan Tangan Indonesia).</span>
            @endif
        </p>
    </section>
@endif
