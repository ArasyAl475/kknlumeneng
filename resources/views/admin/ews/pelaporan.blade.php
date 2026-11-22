@extends('layouts.admin')
@section('judul', 'Nomor pelaporan')

@section('isi')
    <x-judul-halaman judul="Nomor pelaporan (WhatsApp)" keterangan="Laporan warga dari aplikasi HP dikirim ke nomor ini. Ganti di sini, aplikasi ikut berubah tanpa perlu diperbarui." />

    <div class="space-y-4">
        <x-kartu judul="Nomor yang berlaku sekarang">
            @if ($pelaporan['tersedia'])
                <p class="text-2xl font-bold">{{ $pelaporan['nomor_tampil'] }}</p>
                <p class="mt-1 text-sm text-redup">Ditulis untuk WhatsApp sebagai {{ $pelaporan['nomor'] }}.</p>
                <x-tombol varian="kedua" :href="$pelaporan['tautan_wa']" target="_blank" rel="noopener" class="mt-4">Coba buka di WhatsApp</x-tombol>
            @else
                <p class="font-bold">Belum ada nomor.</p>
                <p class="mt-1 text-sm text-redup">Selama kosong, tombol lapor di aplikasi HP tidak bisa mengirim laporan. Isi nomor di bawah.</p>
            @endif
        </x-kartu>

        <x-kartu judul="Ganti nomor">
            <form method="POST" action="{{ route('admin.ews.pelaporan.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-bidang nama="nomor_wa" label="Nomor HP / WhatsApp" type="tel" inputmode="tel" autocomplete="off" required
                    :value="old('nomor_wa', $pelaporan['nomor_tampil'])"
                    bantuan="Boleh ditulis 0812 3456 7890, +62 812-3456-7890, atau 6281234567890. Pastikan nomor ini aktif di WhatsApp." />
                <x-tombol>Simpan nomor</x-tombol>
            </form>
        </x-kartu>

        <x-kartu judul="Untuk pembuat aplikasi HP">
            <p class="text-sm">Aplikasi mengambil nomor dari alamat berikut (tanpa login, tanpa kunci):</p>
            <p class="mt-2 break-all rounded-lg bg-biru-lembut px-3 py-2 font-mono text-sm">GET {{ route('ews.api.pelaporan') }}</p>
            <p class="mt-3 text-sm">Penjelasan lengkap dan contoh kode ada di <span class="font-mono">docs/ews/ews_flutter.md</span>, bagian "Nomor pelaporan".</p>
        </x-kartu>
    </div>
@endsection
