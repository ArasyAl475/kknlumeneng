@extends('layouts.admin')
@section('judul', 'Daftar wilayah')
@push('skrip')
    @vite('resources/js/ews/admin-umum.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Daftar wilayah" keterangan="ID alat dipasang di kode ESP32 sebagai WILAYAH_ID.">
        <x-tombol :href="route('admin.ews.wilayah.create')">Tambah wilayah</x-tombol>
    </x-judul-halaman>

    <x-kartu>
        @if ($wilayah->isEmpty())
            <p class="text-redup">Belum ada wilayah. Tambahkan wilayah pertama, lalu pasang ID alatnya di ESP32.</p>
        @endif
        <ul class="divide-y divide-garis-halus" data-paginasi="10">
            @foreach ($wilayah as $w)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="min-w-0 flex-1 basis-56">
                        <p class="font-bold">{{ $w->nama }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span>ID alat: <strong class="text-xl">{{ $w->id }}</strong></span>
                            <x-lencana :status="\App\Services\Ews\PenilaianStatus::statusTerkini($w->pembacaanTerbaru?->status, $w->pembacaanTerbaru?->pemicu, $w->pembacaanTerbaru?->only(\App\Services\Ews\PenilaianStatus::SENSOR_DINILAI) ?? [], $ambang, $w->status_awas)['status']" />
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

    @if ($terhapus->isNotEmpty())
        <x-kartu judul="Wilayah yang dihapus" class="mt-4">
            <ul class="divide-y divide-garis-halus">
                @foreach ($terhapus as $w)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-2">
                        <span>{{ $w->nama }} <span class="text-redup">(ID alat {{ $w->id }})</span></span>
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
