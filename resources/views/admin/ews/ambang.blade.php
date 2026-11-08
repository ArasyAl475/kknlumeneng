@extends('layouts.admin')
@section('judul', 'Ambang batas sensor')

@section('isi')
    <x-judul-halaman judul="Ambang batas sensor" keterangan="Di atas batas waspada, status menjadi Waspada. Di atas batas validasi, admin diminta memeriksa." />
    <form method="POST" action="{{ route('admin.ews.ambang.update') }}" class="space-y-4">
        @csrf
        @method('PUT')
        @foreach ($ambang as $kunci => $batas)
            <x-kartu :judul="$sensor[$kunci]['nama'].' ('.trim($sensor[$kunci]['satuan']).')'">
                @if (in_array($kunci, ['kemiringan', 'intensitas_hujan'], true))
                    <p class="mb-3 text-sm font-bold">Nilai masih sementara. Tentukan angka sebenarnya setelah alat dikalibrasi di lapangan.</p>
                @endif
                @if ($kunci === 'intensitas_hujan')
                    <p class="mb-3 text-sm">Sensor hujan hanya mengukur seberapa basah papannya (0% kering, 100% basah kuyup), bukan jumlah hujan dalam mm. Papan kecil cepat penuh, jadi angka tinggi belum tentu hujan sangat deras.</p>
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-bidang :nama="$kunci.'_waspada'" label="Batas waspada" type="number" step="0.1" min="0" required :value="old($kunci.'_waspada', $batas['waspada'])" inputmode="decimal" />
                    <x-bidang :nama="$kunci.'_validasi'" label="Batas validasi" type="number" step="0.1" min="0" required :value="old($kunci.'_validasi', $batas['validasi'])" inputmode="decimal" />
                </div>
            </x-kartu>
        @endforeach
        <x-tombol>Simpan ambang</x-tombol>
    </form>
@endsection
