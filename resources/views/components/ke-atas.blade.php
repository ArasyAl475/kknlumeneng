{{-- Tombol bulat "kembali ke atas" di pojok kanan bawah. Muncul setelah halaman digulir ke bawah
     (resources/js/app.js, [data-ke-atas]); cincin di sekelilingnya terisi sesuai jauhnya gulir.
     Saat tersembunyi memakai visibility: hidden, jadi tidak bisa terfokus oleh keyboard. --}}
<button type="button" data-ke-atas aria-label="Kembali ke atas halaman"
        class="group invisible fixed bottom-5 right-5 z-[900] grid size-14 translate-y-4 place-items-center rounded-full bg-navy text-white opacity-0 shadow-angkat ring-1 ring-white/15 transition-[opacity,transform,visibility,background-color] duration-300 hover:bg-biru focus-visible:outline-offset-4 data-[tampil]:visible data-[tampil]:translate-y-0 data-[tampil]:opacity-100 motion-safe:hover:-translate-y-1 sm:bottom-6 sm:right-6">
    <svg class="absolute inset-0 size-full -rotate-90" viewBox="0 0 56 56" aria-hidden="true">
        <circle cx="28" cy="28" r="25" fill="none" stroke="currentColor" stroke-opacity="0.18" stroke-width="3" />
        <circle data-ke-atas-cincin cx="28" cy="28" r="25" fill="none" stroke="var(--color-aksen-terang)" stroke-width="3" stroke-linecap="round" stroke-dasharray="157.08" stroke-dashoffset="157.08" />
    </svg>
    <svg class="relative size-6 motion-safe:transition-transform motion-safe:duration-200 group-hover:-translate-y-0.5" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 15 6-6 6 6" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>
