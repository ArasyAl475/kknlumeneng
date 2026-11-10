{{-- Tombol ke tab Google Sheets milik $jenis. Tidak tampil bila sinkron Sheets belum diaktifkan. --}}
@if (app(\App\Services\Toko\GoogleSheets::class)->adaAlamat())
    <x-tombol varian="kedua" :href="route('admin.toko.sheets.buka', $jenis)" target="_blank" rel="noopener">
        Buka Google Sheets
        @include('toko.partials.ikon-keluar')
    </x-tombol>
@endif
