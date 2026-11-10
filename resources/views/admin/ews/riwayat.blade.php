@extends('layouts.admin')
@section('judul', 'Riwayat pembacaan')

@section('isi')
    <x-judul-halaman judul="Riwayat pembacaan" keterangan="Semua data sensor yang tersimpan." />

    <x-kartu class="mb-4">
        <form method="GET" action="{{ route('admin.ews.riwayat.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
            <div>
                <label for="wilayah" class="mb-1 block font-bold">Wilayah</label>
                <select id="wilayah" name="wilayah" class="isian">
                    <option value="">Semua wilayah</option>
                    @foreach ($wilayah as $w)
                        <option value="{{ $w->id }}" @selected((string) request('wilayah') === (string) $w->id)>{{ $w->nama }}{{ $w->trashed() ? ' (dihapus)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <x-bidang nama="dari" label="Dari tanggal" type="date" :value="request('dari')" />
            <x-bidang nama="sampai" label="Sampai tanggal" type="date" :value="request('sampai')" />
            <div class="flex flex-wrap gap-2">
                <x-tombol>Tampilkan</x-tombol>
                <x-tombol varian="kedua" :href="route('admin.ews.riwayat.ekspor', request()->only(['wilayah', 'dari', 'sampai']))">Unduh CSV</x-tombol>
            </div>
        </form>
    </x-kartu>

    <x-kartu>
        @if ($pembacaan->isEmpty())
            <p class="text-redup">Tidak ada data pada pilihan ini. Coba longgarkan filter wilayah atau tanggal.</p>
        @else
            <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel riwayat, bisa digeser ke samping">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-garis">
                            @foreach (['Waktu (WIB)', 'Wilayah', 'Status', 'Tanah', 'Hujan', 'Miring', 'Udara', 'Pemicu', 'Validasi'] as $k)
                                <th scope="col" class="px-2 py-2 whitespace-nowrap">{{ $k }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pembacaan as $p)
                            <tr class="border-b border-garis-halus">
                                <td class="px-2 py-2 whitespace-nowrap">{{ $p->created_at->format('d-m-Y H:i') }}</td>
                                <td class="px-2 py-2">{{ $p->wilayah->nama }}</td>
                                <td class="px-2 py-2"><x-lencana :status="$p->status" /></td>
                                @foreach (['kelembapan_tanah', 'intensitas_hujan', 'kemiringan', 'kelembapan_udara'] as $s)
                                    <td class="px-2 py-2 whitespace-nowrap">{{ $p->{$s} === null ? '-' : \App\Services\Ews\Sensor::teks($s, $p->{$s}) }}</td>
                                @endforeach
                                <td class="min-w-40 px-2 py-2">{{ $p->pemicu }}</td>
                                <td class="px-2 py-2">{{ $p->validasi }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $pembacaan->links('admin.ews.halaman') }}</div>
        @endif
    </x-kartu>
@endsection
