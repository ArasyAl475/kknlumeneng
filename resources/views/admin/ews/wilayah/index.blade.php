@extends('layouts.admin')
@section('judul', 'Daftar wilayah')
@push('skrip')
    @vite('resources/js/ews/admin-umum.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Daftar wilayah" keterangan="ID alat dipasang di kode ESP32 sebagai WILAYAH_ID. Angkanya bisa diganti lewat tombol Ubah.">
        <x-tombol :href="route('admin.ews.wilayah.create')">Tambah wilayah</x-tombol>
        <x-tombol varian="kedua" :href="route('admin.ews.titik.create')">Tambah titik</x-tombol>
    </x-judul-halaman>

    <x-kartu judul="EWS pintar (terhubung ke web)">
        @if ($wilayah->isEmpty())
            <p class="text-redup">Belum ada wilayah. Tambahkan wilayah pertama, lalu pasang ID alatnya di ESP32.</p>
        @endif
        <ul class="divide-y divide-garis-halus" data-paginasi="10">
            @foreach ($wilayah as $w)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="min-w-0 flex-1 basis-56">
                        <p class="font-bold">{{ $w->nama }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span>ID alat: <strong class="text-xl">{{ $w->id_alat }}</strong></span>
                            <x-lencana :status="\App\Services\Ews\PenilaianStatus::statusTerkini($w->pembacaanTerbaru?->status, $w->pembacaanTerbaru?->pemicu, $w->pembacaanTerbaru?->only(\App\Services\Ews\PenilaianStatus::SENSOR_DINILAI) ?? [], $ambang, $w->status_awas)['status']" />
                        </p>
                        <p class="mt-1 text-sm text-redup">
                            @if ($w->lintang !== null)
                                Lokasi alat: {{ $w->lintang }}, {{ $w->bujur }}
                            @else
                                Lokasi alat belum diisi
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-tombol varian="kedua" :href="route('admin.ews.wilayah.edit', $w)" aria-label="Ubah {{ $w->nama }}">Ubah</x-tombol>
                        <form method="POST" action="{{ route('admin.ews.wilayah.destroy', $w) }}" data-konfirmasi="Hapus wilayah {{ $w->nama }}? Riwayat datanya tetap tersimpan, tetapi alat dengan ID ini akan ditolak.">
                            @csrf
                            @method('DELETE')
                            <x-tombol varian="kedua" aria-label="Hapus {{ $w->nama }}">Hapus</x-tombol>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-kartu>

    <x-kartu judul="Titik lain di peta (tidak terhubung)" class="mt-4">
        @if ($titik->isEmpty())
            <p class="text-redup">Belum ada titik. Tambahkan EWS alarm atau titik rawan longsor lewat tombol Tambah titik.</p>
        @endif
        <ul class="divide-y divide-garis-halus">
            @foreach ($titik as $t)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="min-w-0 flex-1 basis-56">
                        <p class="font-bold">{{ $t->nama }}</p>
                        <p class="mt-1 text-sm text-redup">{{ $t->namaJenis() }} · {{ $t->lintang }}, {{ $t->bujur }}@if ($t->poligon) · ada batas area @endif</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-tombol varian="kedua" :href="route('admin.ews.titik.edit', $t)" aria-label="Ubah {{ $t->nama }}">Ubah</x-tombol>
                        <form method="POST" action="{{ route('admin.ews.titik.destroy', $t) }}" data-konfirmasi="Hapus titik {{ $t->nama }} dari peta?">
                            @csrf
                            @method('DELETE')
                            <x-tombol varian="kedua" aria-label="Hapus {{ $t->nama }}">Hapus</x-tombol>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-kartu>

    @if ($terhapus->isNotEmpty())
        <x-kartu judul="Wilayah yang dihapus" class="mt-4">
            <ul class="divide-y divide-garis-halus">
                @foreach ($terhapus as $w)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-2">
                        <span>{{ $w->nama }} <span class="text-redup">(ID alat dilepas saat dihapus)</span></span>
                        <form method="POST" action="{{ route('admin.ews.wilayah.pulihkan', $w->id) }}">
                            @csrf
                            <x-tombol varian="kedua" aria-label="Pulihkan {{ $w->nama }}">Pulihkan</x-tombol>
                        </form>
                    </li>
                @endforeach
            </ul>
        </x-kartu>
    @endif
@endsection
