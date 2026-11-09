// Penampil foto layar penuh: foto diperbesar dan bisa digeser kiri-kanan (jari, tombol, atau panah keyboard).
// Geseran memakai scroll-snap bawaan browser, jadi terasa alami di HP tanpa pustaka tambahan.
// Data foto diambil dari atribut data-* pada setiap kotak foto (profil/partials/kotak-foto.blade.php).
import './galeri.css';

const GERAK_HALUS = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

let kotak = null;
let daftar = [];
let sekarang = 0;
let pemicu = null;

// <dialog> belum ada di browser sangat lama; di sana kotak foto tetap tautan biasa ke halaman foto.
if (typeof HTMLDialogElement === 'function') {
    document.addEventListener('click', (e) => {
        const tautan = e.target.closest('a[data-foto]');
        if (!tautan || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;

        const wadah = tautan.closest('[data-galeri]');
        if (!wadah) return;

        e.preventDefault();
        const semua = [...wadah.querySelectorAll('a[data-foto]')];
        buka(semua, semua.indexOf(tautan), tautan);
    });
}

function siapkanKotak() {
    kotak = document.createElement('dialog');
    kotak.className = 'kotak-lampu';
    kotak.setAttribute('aria-label', 'Penampil foto');
    kotak.innerHTML = `
        <div class="kotak-lampu__atas">
            <span class="kotak-lampu__hitung" aria-live="polite"></span>
            <button type="button" class="kotak-lampu__tombol" data-aksi="tutup" aria-label="Tutup penampil foto">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="kotak-lampu__jalur"></div>
        <button type="button" class="kotak-lampu__tombol kotak-lampu__geser kotak-lampu__geser--kiri" data-aksi="sebelum" aria-label="Foto sebelumnya">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg>
        </button>
        <button type="button" class="kotak-lampu__tombol kotak-lampu__geser kotak-lampu__geser--kanan" data-aksi="sesudah" aria-label="Foto berikutnya">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
        </button>
        <div class="kotak-lampu__info">
            <p class="kotak-lampu__judul"></p>
            <p class="kotak-lampu__tanggal"></p>
            <p class="kotak-lampu__keterangan"></p>
            <a class="kotak-lampu__berita" hidden></a>
            <p class="kotak-lampu__petunjuk">Geser ke kiri atau kanan untuk melihat foto lain</p>
        </div>`;
    document.body.append(kotak);

    const jalur = kotak.querySelector('.kotak-lampu__jalur');
    let tunggu = 0;
    jalur.addEventListener('scroll', () => {
        cancelAnimationFrame(tunggu);
        tunggu = requestAnimationFrame(() => {
            const indeks = Math.round(jalur.scrollLeft / jalur.clientWidth);
            if (indeks !== sekarang) tampilkanInfo(indeks);
        });
    });

    kotak.addEventListener('click', (e) => {
        const aksi = e.target.closest('[data-aksi]')?.dataset.aksi;
        if (aksi === 'tutup') kotak.close();
        else if (aksi === 'sebelum') geser(-1);
        else if (aksi === 'sesudah') geser(1);
        // Ketukan di ruang kosong sekitar foto menutup penampil, seperti aplikasi galeri di HP.
        else if (e.target.classList.contains('kotak-lampu__slide')) kotak.close();
    });

    kotak.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') geser(-1);
        if (e.key === 'ArrowRight') geser(1);
    });

    kotak.addEventListener('close', () => {
        document.documentElement.classList.remove('kotak-lampu-terbuka');
        pemicu?.focus({ preventScroll: true });
    });
}

function buka(semua, indeks, dariTautan) {
    if (!kotak) siapkanKotak();

    daftar = semua.map((a) => ({ ...a.dataset }));
    pemicu = dariTautan;

    const jalur = kotak.querySelector('.kotak-lampu__jalur');
    jalur.replaceChildren(
        ...daftar.map((foto, i) => {
            const slide = document.createElement('figure');
            slide.className = 'kotak-lampu__slide';
            const img = document.createElement('img');
            img.alt = foto.judul || '';
            img.decoding = 'async';
            // Foto jauh dimuat belakangan agar hemat kuota; foto yang dibuka dan tetangganya langsung.
            img.loading = Math.abs(i - indeks) <= 1 ? 'eager' : 'lazy';
            img.src = foto.besar;
            slide.append(img);
            return slide;
        }),
    );

    const banyak = daftar.length > 1;
    kotak.querySelectorAll('.kotak-lampu__geser, .kotak-lampu__petunjuk').forEach((el) => (el.hidden = !banyak));

    document.documentElement.classList.add('kotak-lampu-terbuka');
    kotak.showModal();
    // Posisi baru bisa dihitung setelah penampil tampil di layar.
    requestAnimationFrame(() => {
        jalur.scrollTo({ left: indeks * jalur.clientWidth, behavior: 'instant' });
        tampilkanInfo(indeks);
    });
}

function geser(arah) {
    const tujuan = Math.min(daftar.length - 1, Math.max(0, sekarang + arah));
    const jalur = kotak.querySelector('.kotak-lampu__jalur');
    jalur.scrollTo({ left: tujuan * jalur.clientWidth, behavior: GERAK_HALUS ? 'smooth' : 'instant' });
}

function tampilkanInfo(indeks) {
    sekarang = indeks;
    const foto = daftar[indeks] ?? {};
    const isi = (pemilih, teks) => {
        const el = kotak.querySelector(pemilih);
        el.textContent = teks || '';
        el.hidden = !teks;
    };

    kotak.querySelector('.kotak-lampu__hitung').textContent = `Foto ${indeks + 1} dari ${daftar.length}`;
    isi('.kotak-lampu__judul', foto.judul);
    isi('.kotak-lampu__tanggal', foto.tanggal);
    isi('.kotak-lampu__keterangan', foto.keterangan);

    const berita = kotak.querySelector('.kotak-lampu__berita');
    berita.hidden = !foto.berita;
    if (foto.berita) {
        berita.href = foto.berita;
        berita.textContent = `Baca cerita ${foto.jenisBerita || 'kegiatan'} ini →`;
    }

    kotak.querySelector('[data-aksi="sebelum"]').disabled = indeks === 0;
    kotak.querySelector('[data-aksi="sesudah"]').disabled = indeks === daftar.length - 1;
}
