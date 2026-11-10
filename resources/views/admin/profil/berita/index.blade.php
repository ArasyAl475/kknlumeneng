@extends('layouts.admin')
@section('judul', 'Berita & artikel')
@push('skrip')
    @vite('resources/js/profil/admin.js')
@endpush

@php $pilihan = ['' => 'Semua'] + \App\Models\Profil\Berita::JENIS; @endphp

@section('isi')
    <x-judul-halaman judul="Berita & artikel" keterangan="Warga hanya melihat tulisan berstatus Terbit. Draf tampil paling atas agar tidak terlupa.">
        <x-tombol :href="route('admin.profil.berita.create')">Tulis baru</x-tombol>
    </x-judul-halaman>

    <nav aria-label="Saring menurut jenis" class="tanpa-batang-gulir mb-4 flex gap-2 overflow-x-auto pb-1">
        @foreach ($pilihan as $kunci => $label)
            @php $dipilih = (string) $jenisTerpilih === (string) $kunci; @endphp
            <a href="{{ route('admin.profil.berita.index', array_filter(['jenis' => $kunci])) }}" @if ($dipilih) aria-current="true" @endif
               @class(['inline-flex min-h-11 shrink-0 items-center rounded-full border-2 px-4 font-bold', 'border-kopi bg-kopi text-white' => $dipilih, 'border-garis-halus bg-permukaan hover:border-kopi' => ! $dipilih])>{{ $label }}</a>
        @endforeach
    </nav>

    <x-kartu>
        @if ($berita->isEmpty())
            <p class="text-redup">Belum ada tulisan. Tekan <span class="font-bold">Tulis baru</span> untuk membuat berita pertama.</p>
        @endif
        <ul class="divide-y divide-garis-halus">
            @foreach ($berita as $b)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="flex min-w-0 flex-1 basis-64 items-center gap-3">
                        @if ($b->urlGambar())
                            <img src="{{ $b->urlGambar() }}" alt="" loading="lazy" class="hidden h-14 w-20 shrink-0 rounded-lg object-cover sm:block">
                        @else
                            <span class="hidden h-14 w-20 shrink-0 items-center justify-center rounded-lg bg-kopi/10 text-kopi sm:flex" aria-hidden="true"><x-ikon nama="berita" class="size-6" /></span>
                        @endif
                        <div class="min-w-0">
                            <p class="font-bold">{{ $b->judul }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                @include('admin.profil.partials.status', ['berita' => $b])
                                <span class="text-redup">{{ $b->labelJenis() }} · {{ $b->sudahTerbit() ? 'terbit '.$b->diterbitkan_pada->format('d-m-Y H:i') : 'diubah '.$b->updated_at->format('d-m-Y H:i') }} WIB</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-tombol varian="kedua" :href="route('admin.profil.berita.edit', $b)" aria-label="Ubah {{ $b->judul }}">Ubah</x-tombol>
                        <form method="POST" action="{{ route('admin.profil.berita.destroy', $b) }}" data-konfirmasi="Hapus tulisan &quot;{{ $b->judul }}&quot; beserta foto sampul dan semua foto dokumentasinya di galeri? Ini tidak bisa dibatalkan.">
                            @csrf
                            @method('DELETE')
                            <x-tombol varian="kedua" aria-label="Hapus {{ $b->judul }}">Hapus</x-tombol>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($berita->hasPages())
            <div class="mt-4">{{ $berita->links('profil.partials.halaman') }}</div>
        @endif
    </x-kartu>
@endsection
