@extends('layouts.admin')
@section('judul', 'Semua produk')
@push('skrip')
    @vite('resources/js/toko/admin.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Semua produk" keterangan="Satu produk dipakai untuk ketiga marketplace. Urutan tampil kecil muncul lebih dulu.">
        <x-tombol :href="route('admin.toko.produk.create')">Tambah produk</x-tombol>
    </x-judul-halaman>

    <x-kartu>
        @if ($produk->isEmpty())
            <p class="text-redup">Belum ada produk. Tekan Tambah produk untuk memulai.</p>
        @endif
        <ul class="divide-y divide-garis-halus">
            @foreach ($produk as $p)
                @php $fotoUtama = $p->foto->first(); @endphp
                <li class="flex flex-wrap items-start gap-3 py-3 first:pt-0 last:pb-0">
                    @if ($fotoUtama)
                        <img src="{{ $fotoUtama->urlKecil() }}" alt="" width="64" height="64" loading="lazy" class="size-16 shrink-0 rounded object-cover">
                    @else
                        <span class="grid size-16 shrink-0 place-items-center rounded bg-kosong-lembut text-redup"><x-ikon nama="toko" /><span class="sr-only">Belum ada foto</span></span>
                    @endif
                    <div class="min-w-0 flex-1 basis-52">
                        <p class="font-bold">{{ $p->nama }}</p>
                        <p class="text-sm">{{ $p->hargaTeks() ?? 'Harga belum diisi' }} · {{ $p->foto->count() }} foto · {{ $p->tampil ? 'Tampil di situs' : 'Disembunyikan' }}</p>
                        <p class="mt-1 flex flex-wrap gap-2 text-sm font-bold">
                            @foreach (\App\Services\Toko\DaftarMarketplace::KUNCI as $kunci)
                                @if ($p->tautanUntuk($kunci))
                                    <span data-warna="hijau" class="status-lembut inline-flex items-center gap-1 rounded px-2"><x-ikon nama="aman" class="size-4" />{{ \App\Services\Toko\DaftarMarketplace::nama($kunci) }} terisi</span>
                                @else
                                    <span data-warna="kosong" class="status-lembut inline-flex items-center gap-1 rounded px-2"><x-ikon nama="kosong" class="size-4" />{{ \App\Services\Toko\DaftarMarketplace::nama($kunci) }} kosong</span>
                                @endif
                            @endforeach
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-tombol varian="kedua" :href="route('admin.toko.produk.edit', $p)" aria-label="Ubah {{ $p->nama }}">Ubah</x-tombol>
                        <form method="POST" action="{{ route('admin.toko.produk.destroy', $p) }}" data-konfirmasi="Hapus {{ $p->nama }} dari situs? Produk bisa dipulihkan lagi nanti.">
                            @csrf
                            @method('DELETE')
                            <x-tombol varian="kedua" aria-label="Hapus {{ $p->nama }}">Hapus</x-tombol>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-kartu>

    @if ($terhapus->isNotEmpty())
        <x-kartu judul="Produk yang dihapus" class="mt-4">
            <ul class="divide-y divide-garis-halus">
                @foreach ($terhapus as $p)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-2">
                        <span>{{ $p->nama }}</span>
                        <form method="POST" action="{{ route('admin.toko.produk.pulihkan', $p->id) }}">
                            @csrf
                            <x-tombol varian="kedua" aria-label="Pulihkan {{ $p->nama }}">Pulihkan</x-tombol>
                        </form>
                    </li>
                @endforeach
            </ul>
        </x-kartu>
    @endif
@endsection
