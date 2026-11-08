@php
    $fotoUtama = $produk->foto->first();
    $harga = $produk->hargaTeks();
    $adaStok = $produk->adaStok();
@endphp
<li class="min-w-0">
    <a href="{{ route('toko.produk', [$kunci, $produk]) }}" @class(['toko-kartu toko-kartu--tautan toko-produk', 'toko-produk--habis' => ! $adaStok])>
        <span class="toko-produk__foto">
            @if ($fotoUtama)
                <img src="{{ $fotoUtama->urlKecil() }}" alt="" width="400" height="400" loading="lazy" decoding="async">
            @else
                <span class="grid size-full place-items-center text-redup">
                    <x-ikon nama="toko" class="size-10" />
                    <span class="sr-only">Foto belum tersedia</span>
                </span>
            @endif
            @unless ($adaStok)
                <span data-warna="kosong" class="status-lembut toko-pil toko-pil--kecil toko-produk__lencana"><x-ikon nama="kosong" class="size-4" />Stok habis</span>
            @endunless
        </span>
        <span class="toko-produk__isi">
            <span class="toko-produk__nama">{{ $produk->nama }}</span>
            <span @class(['toko-produk__harga', 'toko-produk__harga--kosong' => ! $harga])>{{ $harga ?? 'Lihat harga di marketplace' }}</span>
        </span>
    </a>
</li>
