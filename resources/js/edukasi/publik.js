// Pemutar YouTube baru dibuat saat warga menekan "Putar video", supaya halaman materi
// tetap ringan dan tidak memakai kuota untuk video yang tidak ditonton.
// Memakai youtube-nocookie agar YouTube tidak menaruh cookie sebelum video diputar.
document.querySelectorAll('a[data-youtube]').forEach((tombol) => {
    tombol.addEventListener('click', (e) => {
        e.preventDefault();

        const kode = encodeURIComponent(tombol.dataset.youtube);
        const bingkai = document.createElement('iframe');
        bingkai.src = `https://www.youtube-nocookie.com/embed/${kode}?autoplay=1&rel=0`;
        bingkai.title = `Video: ${tombol.dataset.judul}`;
        bingkai.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
        bingkai.allowFullscreen = true;
        bingkai.referrerPolicy = 'strict-origin-when-cross-origin';
        bingkai.className = 'mt-4 aspect-video w-full rounded-lg border border-garis-halus bg-black';

        tombol.replaceWith(bingkai);
        bingkai.focus();
    });
});
