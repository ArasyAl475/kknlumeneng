{{-- Isian lintang dan bujur, dipakai form wilayah dan form titik. Skripnya: resources/js/ews/admin-wilayah.js --}}
<fieldset class="rounded-lg border border-garis-halus p-4">
    <legend class="px-1 font-bold">{{ $judul }}</legend>
    <p class="mb-3 text-sm text-redup">{{ $bantuan }} Salin dari Google Maps lalu tempel "lintang, bujur" ke kolom Lintang, otomatis terpecah.</p>
    <div class="grid gap-3 sm:grid-cols-2">
        <x-bidang nama="lintang" label="Lintang (contoh -7.185390)" inputmode="decimal" autocomplete="off" :required="$wajib" :value="old('lintang', $lintang)" />
        <x-bidang nama="bujur" label="Bujur (contoh 109.588069)" inputmode="decimal" autocomplete="off" :required="$wajib" :value="old('bujur', $bujur)" />
    </div>
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <x-tombol type="button" varian="kedua" id="pilih-titik">Pilih titik di peta</x-tombol>
        <p id="status-titik" class="text-sm font-bold" role="status"></p>
    </div>
</fieldset>
