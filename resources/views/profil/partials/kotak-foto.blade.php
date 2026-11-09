{{-- Satu kotak foto galeri. Diketuk = membuka penampil foto layar penuh yang bisa digeser
     (resources/js/profil/galeri.js, untuk semua kotak dalam wadah [data-galeri] yang sama).
     Tanpa JavaScript, kotak ini tautan biasa ke halaman foto.
     Variabel: $foto (Foto), $tautan (opsional), $tampilJudul (opsional, bawaan true),
     $jembatan (opsional, bawaan true: tampilkan tautan "Baca ceritanya" ke beritanya). --}}
@php
    $tautan ??= route('galeri.show', $foto);
    $tampilJudul ??= true;
    $jembatan ??= true;
    $beritaFoto = $jembatan && $foto->terhubung() && $foto->berita?->sudahTerbit() ? $foto->berita : null;
@endphp
<a href="{{ $tautan }}" data-foto
   data-besar="{{ $foto->urlGambar() }}"
   data-judul="{{ $foto->judulTampil() }}"
   data-tanggal="{{ $foto->tanggalTampil()?->format('d-m-Y') }}"
   data-keterangan="{{ $foto->keteranganTampil() }}"
   @if ($beritaFoto) data-berita="{{ route('berita.show', $beritaFoto->slug) }}" data-jenis-berita="{{ strtolower($beritaFoto->labelJenis()) }}" @endif
   class="group relative block overflow-hidden rounded-2xl bg-kopi/10 shadow-sm focus-visible:outline-offset-4">
    <img src="{{ $foto->urlGambar() }}" alt="{{ $foto->judulTampil() }}" loading="lazy" decoding="async"
         class="aspect-square w-full object-cover motion-safe:transition-transform motion-safe:duration-300 group-hover:scale-105">
    @if ($tampilJudul)
        {{-- Gradasi gelap di bawah agar judul putih tetap terbaca di atas foto terang. --}}
        <span class="absolute inset-x-0 bottom-0 flex items-end gap-1.5 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-3 pt-10 text-left text-sm font-bold leading-snug text-white">
            @if ($foto->terhubung())
                <x-ikon nama="berita" class="mb-0.5 size-4 opacity-90" />
            @endif
            <span class="line-clamp-2">{{ $foto->judulTampil() }}</span>
        </span>
    @endif
    <span class="absolute right-2 top-2 flex size-8 items-center justify-center rounded-full bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100" aria-hidden="true">
        <x-ikon nama="perbesar" class="size-4" />
    </span>
</a>
