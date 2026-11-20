@props(['nama', 'label', 'bantuan' => null])
{{-- Isian formulir dengan label dan pesan galat yang terhubung ke input. --}}
@php($sandi = $attributes->get('type') === 'password')
<div {{ $attributes->only('class') }}>
    <label for="{{ $nama }}" class="mb-1 block font-bold">{{ $label }}</label>
    @if ($sandi)
        <div class="relative">
    @endif
    <input id="{{ $nama }}" name="{{ $nama }}" {{ $attributes->except('class')->merge(['type' => 'text', 'value' => old($nama)])->class('isian') }}
        @if ($sandi) style="padding-right: 3rem" @endif
        @error($nama) aria-invalid="true" aria-describedby="{{ $nama }}-galat" @enderror>
    @if ($sandi)
            {{-- Tombol mata: tersembunyi sampai skrip di bawah jalan, jadi tanpa JavaScript tidak ada tombol mati. --}}
            <button type="button" class="absolute right-0 flex w-11 items-center justify-center rounded-lg text-redup" style="top: 0; bottom: 0"
                data-lihat-sandi aria-controls="{{ $nama }}" aria-pressed="false" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi" hidden>
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-2.6 3.4M6.6 6.6C3.7 8.4 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18" /></svg>
            </button>
        </div>
        {{-- Skrip sebaris (bukan di resources/js) agar server cukup git pull, tanpa membangun ulang public/build. --}}
        @once
            <script>
                (function () {
                    function atur(tombol, lihat) {
                        var isian = document.getElementById(tombol.getAttribute('aria-controls'));
                        var teks = lihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
                        isian.type = lihat ? 'text' : 'password';
                        tombol.setAttribute('aria-pressed', lihat ? 'true' : 'false');
                        tombol.setAttribute('aria-label', teks);
                        tombol.title = teks;
                        tombol.children[0].hidden = lihat;
                        tombol.children[1].hidden = !lihat;
                    }
                    document.addEventListener('click', function (e) {
                        var tombol = e.target.closest && e.target.closest('[data-lihat-sandi]');
                        if (tombol) atur(tombol, tombol.getAttribute('aria-pressed') !== 'true');
                    });
                    // Kembalikan ke titik-titik sebelum dikirim, supaya browser tidak menyimpan sandi sebagai isian teks biasa.
                    document.addEventListener('submit', function (e) {
                        e.target.querySelectorAll('[data-lihat-sandi]').forEach(function (t) { atur(t, false); });
                    }, true);
                    document.addEventListener('DOMContentLoaded', function () {
                        document.querySelectorAll('[data-lihat-sandi]').forEach(function (t) { t.hidden = false; });
                    });
                })();
            </script>
        @endonce
    @endif
    @if ($bantuan)
        <p class="mt-1 text-sm text-redup">{{ $bantuan }}</p>
    @endif
    @error($nama)
        <p id="{{ $nama }}-galat" class="mt-1 text-sm font-bold text-awas">{{ $message }}</p>
    @enderror
</div>
