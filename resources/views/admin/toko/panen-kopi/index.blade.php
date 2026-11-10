@extends('layouts.admin')
@section('judul', 'Panen kopi')
@push('skrip')
    @vite('resources/js/toko/admin.js')
@endpush

@php use App\Services\Toko\FormatHasil; @endphp

@section('isi')
    <x-judul-halaman judul="Panen kopi" keterangan="Catatan tiap panen. Warga hanya melihat angka totalnya.">
        <x-tombol varian="kedua" :href="route('admin.toko.impor.show', 'panen-kopi')">Impor dari Excel</x-tombol>
        <x-tombol :href="route('admin.toko.panen-kopi.create')">Catat panen kopi</x-tombol>
    </x-judul-halaman>

    @include('admin.toko.partials.ringkasan', ['angka' => [
        'Total panen kopi' => FormatHasil::kg($ringkasan['panen']),
        'Total limbah kopi' => FormatHasil::kg($ringkasan['limbah']),
    ]])

    <section class="rounded-2xl border border-garis-halus bg-permukaan p-4 shadow-sm sm:p-5">
        @if ($panen->isEmpty())
            @include('admin.toko.partials.kosong', ['ikon' => 'kopi', 'pesan' => 'Belum ada catatan panen kopi. Tekan Catat panen kopi untuk mengisi yang pertama.'])
        @else
            <ul class="divide-y divide-garis-halus">
                @foreach ($panen as $p)
                    @php $hari = FormatHasil::hari($p->tanggal_panen); @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="flex min-w-0 flex-1 basis-64 gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-kopi/10 text-kopi">@include('toko.partials.ikon', ['nama' => 'kopi'])</span>
                            <div class="min-w-0">
                                <p class="font-bold">{{ $hari }}</p>
                                <p class="text-redup">{{ $p->jenis_kopi }} · {{ $p->tempat_panen }}</p>
                                @if ($p->nama_petani)
                                    <p class="text-redup">Petani/kelompok: {{ $p->nama_petani }}</p>
                                @endif
                                <p class="mt-1">Panen <strong>{{ FormatHasil::kg($p->jumlah_kg) }}</strong> · Limbah <strong>{{ FormatHasil::kg($p->limbah_kg) }}</strong></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-tombol varian="kedua" :href="route('admin.toko.panen-kopi.edit', $p)" aria-label="Ubah panen kopi {{ $hari }}">Ubah</x-tombol>
                            <form method="POST" action="{{ route('admin.toko.panen-kopi.destroy', $p) }}" data-konfirmasi="Hapus catatan panen kopi {{ $hari }}? Catatan tidak bisa dipulihkan.">
                                @csrf
                                @method('DELETE')
                                <x-tombol varian="kedua" aria-label="Hapus panen kopi {{ $hari }}">Hapus</x-tombol>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-3">{{ $panen->links('admin.ews.halaman') }}</div>
        @endif
    </section>
@endsection
