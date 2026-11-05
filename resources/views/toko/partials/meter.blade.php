{{-- Teks persen berada di samping meter, jadi batangnya sendiri disembunyikan dari pembaca layar. --}}
<div class="{{ $jalur }} mt-2 h-2 overflow-hidden rounded-full" aria-hidden="true">
    <div class="{{ $isi }} h-full rounded-full" style="width: {{ min($persen, 100) }}%"></div>
</div>
