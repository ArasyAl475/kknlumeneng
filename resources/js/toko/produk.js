// Foto kecil menggeser galeri ke samping tanpa membuat halaman ikut melompat ke atas.
// Tanpa JavaScript, tautan #foto-n tetap berfungsi.
const galeri = document.getElementById('galeri');
const tenang = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

document.querySelectorAll('[data-foto]').forEach((tautan) => {
    tautan.addEventListener('click', (e) => {
        const tujuan = document.getElementById(tautan.dataset.foto);
        if (!galeri || !tujuan) return;
        e.preventDefault();
        galeri.scrollTo({ left: tujuan.offsetLeft - galeri.offsetLeft, behavior: tenang ? 'auto' : 'smooth' });
    });
});
