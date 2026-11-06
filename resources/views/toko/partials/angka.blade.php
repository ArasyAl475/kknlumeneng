{{-- Angka besar dengan satuan kecil. Angka 0 diredam supaya halaman tidak terlihat "penuh nol". --}}
@php $kosong = $kosong ?? false; @endphp
<p @class(['angka', $ukuran ?? 'angka--sedang', 'angka--kosong' => $kosong])>
    @if (! empty($awalan))<span class="angka__satuan">{{ $awalan }}</span>@endif
    <span class="angka__nilai">{{ $nilai }}</span>
    @if (! empty($satuan))<span class="angka__satuan">{{ $satuan }}</span>@endif
</p>
