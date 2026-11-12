{{-- Penanda pembaruan otomatis (resources/js/toko/lib/segar-otomatis.js): bagian bertanda data-segar-bagian
     diganti isinya tanpa memuat ulang halaman bila data hasil panen berubah.
     $versi dihitung controller SEBELUM data diambil, supaya perubahan saat halaman dibuat tetap terdeteksi. --}}
<div data-segar data-alamat="{{ route('toko.hasil.versi') }}" data-versi="{{ $versi }}">
    <p class="sr-only" aria-live="polite" data-segar-umumkan></p>
</div>
