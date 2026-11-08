@extends('layouts.admin')
@section('judul', 'Simulator')
@push('skrip')
    @vite('resources/js/ews/admin-umum.js')
@endpush

@section('isi')
    <x-judul-halaman judul="Simulator" keterangan="Mengirim data buatan lewat alur yang sama dengan alat: laporan validasi ikut berjalan. Halaman ini tidak ada di server produksi." />

    <x-kartu>
        @if ($wilayah->isEmpty())
            <p class="text-redup">Belum ada wilayah. Tambahkan dulu di Daftar wilayah.</p>
        @else
            <form method="POST" action="{{ route('admin.ews.simulator.kirim') }}" class="space-y-4" id="form-simulator">
                @csrf
                <div>
                    <label for="wilayah_id" class="mb-1 block font-bold">Wilayah</label>
                    <select id="wilayah_id" name="wilayah_id" class="isian">
                        @foreach ($wilayah as $w)
                            <option value="{{ $w->id }}" @selected((string) old('wilayah_id') === (string) $w->id)>{{ $w->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <p class="mb-1 font-bold">Isi cepat</p>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $isiCepat = [
                                'Aman' => ['kelembapan_tanah' => 30, 'intensitas_hujan' => 5, 'kemiringan' => 1, 'kelembapan_udara' => 75],
                                'Waspada' => ['kelembapan_tanah' => $ambang['kelembapan_tanah']['waspada'] + 1, 'intensitas_hujan' => 5, 'kemiringan' => 1, 'kelembapan_udara' => 80],
                                'Perlu validasi' => ['kelembapan_tanah' => $ambang['kelembapan_tanah']['validasi'] + 1, 'intensitas_hujan' => min(100, $ambang['intensitas_hujan']['validasi'] + 1), 'kemiringan' => 1, 'kelembapan_udara' => 90],
                            ];
                        @endphp
                        @foreach ($isiCepat as $label => $nilai)
                            <x-tombol type="button" varian="kedua" :data-isi-cepat="json_encode($nilai)">{{ $label }}</x-tombol>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-bidang nama="kelembapan_tanah" label="Kelembapan tanah (%)" type="number" step="0.1" min="0" max="100" inputmode="decimal" />
                    <x-bidang nama="intensitas_hujan" label="Intensitas hujan (%)" type="number" step="0.1" min="0" max="100" inputmode="decimal" />
                    <x-bidang nama="kemiringan" label="Kemiringan (derajat)" type="number" step="0.1" min="-90" max="90" />
                    <x-bidang nama="kelembapan_udara" label="Kelembapan udara (%)" type="number" step="0.1" min="0" max="100" inputmode="decimal" />
                </div>
                <p class="text-sm text-redup">Kosongkan sensor untuk meniru sensor yang gagal baca.</p>

                <label class="flex min-h-11 items-center gap-3 font-bold" for="kabel_putus">
                    <input id="kabel_putus" type="checkbox" name="kabel_putus" value="1" class="size-5 shrink-0" @checked(old('kabel_putus'))>
                    Kabel EWS putus (langsung Awas)
                </label>

                <x-tombol>Kirim data simulasi</x-tombol>
            </form>
        @endif
    </x-kartu>
@endsection
