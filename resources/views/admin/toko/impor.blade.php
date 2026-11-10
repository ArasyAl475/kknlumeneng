@extends('layouts.admin')
@section('judul', 'Impor Excel — '.$def['label'])

@php
    $galatBaris = isset($hasil) ? array_values(array_filter($hasil['baris'], fn ($b) => $b['galat'] !== [])) : [];
    $contoh = isset($hasil) ? array_slice($hasil['baris'], 0, 20) : [];
@endphp

@section('isi')
    <x-judul-halaman :judul="'Impor Excel — '.$def['label']" keterangan="Tambahkan banyak catatan sekaligus dari Excel. Catatan lama tidak diubah atau dihapus.">
        <x-tombol varian="kedua" :href="route('admin.toko.'.$jenis.'.index')">Kembali ke daftar</x-tombol>
    </x-judul-halaman>

    <ol class="mb-5 max-w-2xl list-decimal space-y-1 pl-6">
        <li>Unduh templat, lalu isi di Excel (satu catatan per baris, paling banyak {{ $maksBaris }} baris). Petunjuk ada di lembar kedua templat.</li>
        <li>Unggah berkas yang sudah diisi. Sistem memeriksa dulu, belum menyimpan apa pun.</li>
        <li>Kalau semua baris benar, tekan <strong>Simpan</strong>.</li>
    </ol>

    <section class="mb-5 max-w-2xl space-y-4 rounded-2xl border border-garis-halus bg-permukaan p-4 shadow-sm sm:p-6">
        <div>
            <x-tombol varian="kedua" :href="route('admin.toko.impor.templat', $jenis)">Unduh templat Excel</x-tombol>
        </div>

        <form method="POST" action="{{ route('admin.toko.impor.pratinjau', $jenis) }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div>
                <label for="berkas" class="mb-1 block font-bold">Berkas Excel (.xlsx)</label>
                <input id="berkas" name="berkas" type="file" required accept=".xlsx" class="isian"
                    @error('berkas') aria-invalid="true" @enderror>
                <p class="mt-1 text-sm text-redup">Ukuran paling besar 2 MB. Mengunggah berkas yang sama dua kali membuat catatan ganda.</p>
            </div>
            <x-tombol>Periksa berkas</x-tombol>
        </form>
    </section>

    @isset($hasil)
        <section class="max-w-4xl rounded-2xl border border-garis-halus bg-permukaan p-4 shadow-sm sm:p-6" aria-labelledby="judul-hasil">
            <h2 id="judul-hasil" class="text-xl font-bold">Hasil pemeriksaan</h2>

            @if ($hasil['jumlah_galat'] > 0)
                <div role="alert" data-warna="merah" class="status-lembut status-garis mt-3 rounded-xl border-2 p-4">
                    <p class="flex items-center gap-2 font-bold">
                        <x-ikon nama="awas" class="size-5 shrink-0" />
                        Belum bisa disimpan: {{ $hasil['jumlah_galat'] }} dari {{ count($hasil['baris']) }} baris perlu diperbaiki.
                    </p>
                    <p class="mt-1 text-sm">Perbaiki di Excel, lalu unggah lagi. Tidak ada data yang disimpan.</p>
                </div>

                <ul class="mt-4 space-y-3">
                    @foreach (array_slice($galatBaris, 0, 50) as $b)
                        <li class="rounded-xl border border-garis-halus p-3">
                            <p class="font-bold">Baris {{ $b['no'] }} di Excel</p>
                            <ul class="mt-1 list-disc space-y-0.5 pl-6 text-sm">
                                @foreach ($b['galat'] as $galat)
                                    <li>{{ $galat }}</li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
                @if (count($galatBaris) > 50)
                    <p class="mt-3 text-sm text-redup">Dan {{ count($galatBaris) - 50 }} baris lain. Perbaiki yang ini dulu, lalu unggah lagi.</p>
                @endif
            @else
                <div role="status" data-warna="hijau" class="status-lembut status-garis mt-3 rounded-xl border-2 p-4">
                    <p class="flex items-center gap-2 font-bold">
                        <x-ikon nama="aman" class="size-5 shrink-0" />
                        Semua {{ $hasil['jumlah_valid'] }} baris benar dan siap disimpan.
                    </p>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-max text-left">
                        <caption class="mb-2 text-left text-sm text-redup">
                            @if (count($hasil['baris']) > count($contoh))
                                Menampilkan {{ count($contoh) }} baris pertama dari {{ count($hasil['baris']) }}.
                            @else
                                Semua baris yang akan disimpan.
                            @endif
                        </caption>
                        <thead>
                            <tr class="border-b-2 border-garis">
                                <th scope="col" class="px-2 py-2">Baris</th>
                                @foreach ($def['kolom'] as $k)
                                    <th scope="col" class="px-2 py-2">{{ $k['judul'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-garis-halus">
                            @foreach ($contoh as $b)
                                <tr>
                                    <td class="px-2 py-2 text-redup">{{ $b['no'] }}</td>
                                    @foreach ($b['tampil'] as $isi)
                                        <td class="px-2 py-2">{{ $isi }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('admin.toko.impor.simpan', $jenis) }}" class="mt-4 flex flex-wrap gap-2">
                    @csrf
                    <x-tombol>Simpan {{ $hasil['jumlah_valid'] }} catatan</x-tombol>
                    <x-tombol varian="kedua" :href="route('admin.toko.impor.show', $jenis)">Batal</x-tombol>
                </form>
            @endif
        </section>
    @endisset
@endsection
