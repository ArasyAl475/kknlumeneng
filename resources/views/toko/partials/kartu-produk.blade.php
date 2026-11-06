@php $fotoUtama = $produk->foto->first(); @endphp
<li class="min-w-0">
    <a href="{{ route('toko.produk', [$kunci, $produk]) }}" class="group flex h-full flex-col overflow-hidden rounded-lg border border-garis-halus bg-permukaan hover:border-kopi">
        @if ($fotoUtama)
            <img src="{{ $fotoUtama->urlKecil() }}" alt="" width="400" height="400" loading="lazy" decoding="async" class="aspect-square w-full bg-kosong-lembut object-cover">
        @else
            <span class="grid aspect-square w-full place-items-center bg-kosong-lembut text-redup">
                <x-ikon nama="toko" class="size-10" />
                <span class="sr-only">Foto belum tersedia</span>
            </span>
        @endif
        <span class="flex flex-1 flex-col gap-1 p-3">
            <span class="font-bold leading-snug group-hover:underline">{{ $produk->nama }}</span>
            <span class="text-sm">{{ $produk->hargaTeks() ?? 'Lihat harga di marketplace' }}</span>
            @unless ($produk->adaStok())
                <span data-warna="kosong" class="status-lembut mt-auto inline-flex w-fit items-center gap-1 rounded px-2 text-sm font-bold"><x-ikon nama="kosong" class="size-4" />Stok habis</span>
            @endunless
        </span>
    </a>
</li>
