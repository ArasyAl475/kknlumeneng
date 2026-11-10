@extends('layouts.admin')
@section('judul', 'Perangkat desa')
@push('skrip')
    @vite('resources/js/profil/admin.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Perangkat desa" keterangan="Orang-orang di bagan Struktur Pemerintahan. Urutan dalam satu kelompok mengikuti angka urutan, dari kecil ke besar.">
        <x-tombol varian="kedua" :href="route('profil.index').'#pemerintahan'" target="_blank" rel="noopener">Lihat di situs</x-tombol>
        <x-tombol :href="route('admin.profil.perangkat.create')">Tambah perangkat</x-tombol>
    </x-judul-halaman>

    @if ($perangkat->isEmpty())
        <x-kartu>
            <p class="text-redup">Belum ada perangkat desa. Tekan <span class="font-bold">Tambah perangkat</span> dan mulai dari kepala desa.</p>
        </x-kartu>
    @endif

    <div class="space-y-4">
        @foreach (\App\Models\Profil\Perangkat::KELOMPOK as $kelompok => $label)
            @continue(! $perangkat->has($kelompok))
            <x-kartu :judul="$label">
                <ul class="divide-y divide-garis-halus" data-paginasi="10">
                    @foreach ($perangkat[$kelompok] as $p)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="flex min-w-0 flex-1 basis-56 items-center gap-3">
                                @if ($p->urlFoto())
                                    <img src="{{ $p->urlFoto() }}" alt="" loading="lazy" class="size-12 shrink-0 rounded-full object-cover">
                                @else
                                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-kopi/10 font-bold text-kopi" aria-hidden="true">{{ $p->inisial() }}</span>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-bold">{{ $p->nama }}</p>
                                    <p class="text-sm text-redup">{{ $p->jabatan }} · urutan {{ $p->urutan }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-tombol varian="kedua" :href="route('admin.profil.perangkat.edit', $p)" aria-label="Ubah {{ $p->nama }}">Ubah</x-tombol>
                                <form method="POST" action="{{ route('admin.profil.perangkat.destroy', $p) }}" data-konfirmasi="Hapus {{ $p->nama }} ({{ $p->jabatan }}) dari struktur? Ini tidak bisa dibatalkan.">
                                    @csrf
                                    @method('DELETE')
                                    <x-tombol varian="kedua" aria-label="Hapus {{ $p->nama }}">Hapus</x-tombol>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-kartu>
        @endforeach
    </div>
@endsection
