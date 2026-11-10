@extends('layouts.admin')
@section('judul', 'Panen jamur')
@push('skrip')
    @vite('resources/js/toko/admin.js')
@endpush

@php use App\Services\Toko\FormatHasil; @endphp

@section('isi')
    <x-judul-halaman judul="Panen jamur" keterangan="Catatan tiap panen. Warga hanya melihat angka totalnya.">
        @include('admin.toko.partials.tombol-sheets', ['jenis' => 'panen-jamur'])        <x-tombol :href="route('admin.toko.panen-jamur.create')">Catat panen jamur</x-tombol>
    </x-judul-halaman>

    @include('admin.toko.partials.ringkasan', ['angka' => [
        'Total panen jamur' => FormatHasil::kg($ringkasan['total']),
        'Panen jamur dengan cangkang kopi' => FormatHasil::kg($ringkasan['cangkang']),
        ...collect($keuangan)->mapWithKeys(fn ($k) => [
            'Biaya per baglog, '.mb_strtolower($k['label']).' (khusus admin)' => $k['biaya_per_baglog'] === null ? 'Belum ada data' : FormatHasil::rupiah($k['biaya_per_baglog']),
        ])->all(),
    ]])

    <section class="rounded-2xl border border-garis-halus bg-permukaan p-4 shadow-sm sm:p-5">
        @if ($panen->isEmpty())
            @include('admin.toko.partials.kosong', ['ikon' => 'jamur', 'pesan' => 'Belum ada catatan panen jamur. Tekan Catat panen jamur untuk mengisi yang pertama.'])
        @else
            <ul class="divide-y divide-garis-halus">
                @foreach ($panen as $p)
                    @php $hari = FormatHasil::hari($p->tanggal_panen); @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="flex min-w-0 flex-1 basis-64 gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-kopi/10 text-kopi">@include('toko.partials.ikon', ['nama' => 'jamur'])</span>
                            <div class="min-w-0">
                                <p class="font-bold">{{ $hari }}</p>
                                <p class="text-redup">{{ $p->dengan_cangkang_kopi ? 'Dengan cangkang kopi' : 'Tanpa cangkang kopi' }}</p>
                                <p class="mt-1">Panen <strong>{{ FormatHasil::kg($p->jumlah_kg) }}</strong> · <strong>{{ $p->jumlah_baglog === null ? 'Baglog belum dicatat' : number_format($p->jumlah_baglog, 0, ',', '.').' baglog' }}</strong></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-tombol varian="kedua" :href="route('admin.toko.panen-jamur.edit', $p)" aria-label="Ubah panen jamur {{ $hari }}">Ubah</x-tombol>
                            <form method="POST" action="{{ route('admin.toko.panen-jamur.destroy', $p) }}" data-konfirmasi="Hapus catatan panen jamur {{ $hari }}? Catatan tidak bisa dipulihkan.">
                                @csrf
                                @method('DELETE')
                                <x-tombol varian="kedua" aria-label="Hapus panen jamur {{ $hari }}">Hapus</x-tombol>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-3">{{ $panen->links('admin.ews.halaman') }}</div>
        @endif
    </section>
@endsection
