// Galeri Beranda bergiliran: awalnya 5 foto terbaru, lalu tiap beberapa detik satu kotak acak
// berganti foto cadangan dengan efek memudar. Lightbox (profil/galeri.js) membaca kotak saat diketuk,
// jadi foto yang baru masuk ikut bisa diperbesar.
// Berhenti saat: tombol jeda ditekan, kursor/fokus di galeri, galeri di luar layar, tab disembunyikan,
// atau pengguna memilih "kurangi gerakan".
const wadah = document.querySelector('[data-gilir]');
const cadanganTpl = document.querySelector('[data-gilir-cadangan]');
const tombolJeda = document.querySelector('[data-gilir-jeda]');

const JEDA_AWAL = 6000;
const SELANG = 4500;
const LAMA_PUDAR = 700;

if (wadah && cadanganTpl && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const cadangan = [...cadanganTpl.content.querySelectorAll('a[data-foto]')];
    const kotak = [...wadah.children];
    let dijeda = false;
    let disorot = false;
    let terlihat = true;
    let terakhir = -1;
    let pewaktu = 0;
    let sedangGanti = false;

    const bolehJalan = () => !dijeda && !disorot && terlihat && !document.hidden;

    const acak = (n, kecuali) => {
        if (n <= 1) return 0;
        let i;
        do {
            i = Math.floor(Math.random() * n);
        } while (i === kecuali);
        return i;
    };

    // Foto dimuat dulu di belakang layar agar pergantian tidak berkedip kosong di sinyal lemah.
    const muat = (src) =>
        new Promise((selesai) => {
            const img = new Image();
            img.onload = img.onerror = () => selesai();
            img.src = src;
        });

    async function ganti() {
        if (!bolehJalan() || sedangGanti || cadangan.length === 0) return;
        sedangGanti = true;

        const iKotak = acak(kotak.length, terakhir);
        terakhir = iKotak;
        const li = kotak[iKotak];
        const lama = li.querySelector('a[data-foto]');
        const iBaru = Math.floor(Math.random() * cadangan.length);
        const baru = cadangan[iBaru].cloneNode(true);

        const img = baru.querySelector('img');
        img.removeAttribute('loading');
        await muat(img.getAttribute('src'));

        if (!bolehJalan() || li.contains(document.activeElement)) {
            sedangGanti = false;
            return;
        }

        // Foto baru ditumpuk di atas foto lama, memudar masuk, lalu foto lama kembali ke cadangan.
        baru.style.cssText = `position:absolute;inset:0;opacity:0;transition:opacity ${LAMA_PUDAR}ms ease`;
        li.append(baru);
        requestAnimationFrame(() => requestAnimationFrame(() => (baru.style.opacity = '1')));

        setTimeout(() => {
            baru.style.cssText = '';
            lama.remove();
            cadangan.splice(iBaru, 1, lama);
            sedangGanti = false;
        }, LAMA_PUDAR + 50);
    }

    const mulai = (tunda = SELANG) => {
        clearTimeout(pewaktu);
        const ulang = () => {
            ganti();
            pewaktu = setTimeout(ulang, SELANG);
        };
        pewaktu = setTimeout(ulang, tunda);
    };

    wadah.addEventListener('mouseenter', () => (disorot = true));
    wadah.addEventListener('mouseleave', () => (disorot = false));
    wadah.addEventListener('focusin', () => (disorot = true));
    wadah.addEventListener('focusout', () => (disorot = wadah.matches(':hover')));

    if ('IntersectionObserver' in window) {
        new IntersectionObserver(([e]) => (terlihat = e.isIntersecting), { threshold: 0.2 }).observe(wadah);
    }

    if (tombolJeda) {
        tombolJeda.hidden = false;
        const teks = tombolJeda.querySelector('[data-teks]');
        tombolJeda.addEventListener('click', () => {
            dijeda = !dijeda;
            tombolJeda.setAttribute('aria-pressed', String(dijeda));
            teks.textContent = dijeda ? 'Lanjutkan pergantian foto' : 'Jeda pergantian foto';
            tombolJeda.querySelector('[data-ikon-jeda]').toggleAttribute('hidden', dijeda);
            tombolJeda.querySelector('[data-ikon-putar]').toggleAttribute('hidden', !dijeda);
        });
    }

    mulai(JEDA_AWAL);
}
