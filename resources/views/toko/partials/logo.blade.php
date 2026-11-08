{{-- Logo marketplace dari public/images/toko/; selama berkasnya belum ada, tampil huruf pengganti. --}}
@php
    $logo = \App\Services\Toko\DaftarMarketplace::logo($kunci);
    $ukuranKelas = ($besar ?? false) ? 'size-14 text-2xl' : 'size-11 text-xl';
@endphp
@if ($logo)
    <img src="{{ $logo }}" alt="" width="56" height="56" class="{{ $ukuranKelas }} shrink-0 rounded-xl bg-white object-contain p-1 ring-1 ring-black/10">
@else
    <span aria-hidden="true" class="{{ $ukuranKelas }} {{ \App\Services\Toko\DaftarMarketplace::warna($kunci) }} inline-grid shrink-0 place-items-center rounded-xl font-bold text-white">{{ \App\Services\Toko\DaftarMarketplace::huruf($kunci) }}</span>
@endif
