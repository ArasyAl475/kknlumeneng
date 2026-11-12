@extends('layouts.admin')
@section('judul', 'Penjualan jamur')
@push('skrip')
    @vite('resources/js/toko/admin.js')
@endpush

@php use App\Services\Toko\FormatHasil; @endphp

@section('isi')
    @include('toko.partials.penanda-segar')

    <x-judul-halaman judul="Penjualan jamur" keterangan="Catatan tiap penjualan. Warga hanya melihat angka totalnya.">
        @include('admin.toko.partials.tombol-sheets', ['jenis' => 'penjualan-jamur'])        <x-tombol :href="route('admin.toko.penjualan-jamur.create')">Catat penjualan</x-tombol>
    </x-judul-halaman>

    @include('admin.toko.partials.ringkasan', ['angka' => [
        'Total uang penjualan' => FormatHasil::rupiah($ringkasan['rupiah']),
        'Total jamur terjual' => FormatHasil::kg($ringkasan['kg']),
        ...collect($keuangan)->mapWithKeys(fn ($k) => [
            'Harga rata-rata per kg, '.mb_strtolower($k['label']).' (khusus admin)' => $k['harga_per_kg'] === null ? 'Belum ada data' : FormatHasil::rupiah($k['harga_per_kg']),
        ])->all(),
    ]])

    <section class="rounded-2xl border border-garis-halus bg-permukaan p-4 shadow-sm sm:p-5">
        @if ($penjualan->isEmpty())
            @include('admin.toko.partials.kosong', ['ikon' => 'uang', 'pesan' => 'Belum ada catatan penjualan jamur. Tekan Catat penjualan untuk mengisi yang pertama.'])
        @else
            <ul class="divide-y divide-garis-halus" data-segar-bagian="daftar">
                @foreach ($penjualan as $p)
                    @php $hari = FormatHasil::hari($p->tanggal_penjualan); @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="flex min-w-0 flex-1 basis-64 gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-kopi/10 text-kopi">@include('toko.partials.ikon', ['nama' => 'uang'])</span>
                            <div class="min-w-0">
                                <p class="font-bold">{{ $hari }}</p>
                                <p class="text-redup">{{ $p->dengan_cangkang_kopi === null ? 'Media tanam belum dicatat' : ($p->dengan_cangkang_kopi ? 'Dengan cangkang kopi' : 'Tanpa cangkang kopi') }}</p>
                                <p class="mt-1">Terjual <strong>{{ FormatHasil::kg($p->jumlah_kg) }}</strong> · <strong>{{ FormatHasil::rupiah($p->total_rupiah) }}</strong></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-tombol varian="kedua" :href="route('admin.toko.penjualan-jamur.edit', $p)" aria-label="Ubah penjualan jamur {{ $hari }}">Ubah</x-tombol>
                            <form method="POST" action="{{ route('admin.toko.penjualan-jamur.destroy', $p) }}" data-konfirmasi="Hapus catatan penjualan jamur {{ $hari }}? Catatan tidak bisa dipulihkan.">
                                @csrf
                                @method('DELETE')
                                <x-tombol varian="kedua" aria-label="Hapus penjualan jamur {{ $hari }}">Hapus</x-tombol>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-3" data-segar-bagian="halaman">{{ $penjualan->links('admin.ews.halaman') }}</div>
        @endif
    </section>
@endsection
