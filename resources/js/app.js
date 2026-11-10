// Penanda skrip aktif. Kelas yang sama juga dipasang di <head> layouts/app agar tidak berkedip.
document.documentElement.classList.add('js', 'js-siap');
// Skrip ini berjalan, jadi cadangan "paksa tampil" dari <head> tidak diperlukan lagi.
clearTimeout(window.__paksaTampil);

const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// ---------- Muncul saat digulir: [data-muncul] (satu blok) dan [data-stagger] (anak bergantian) ----------
const bagian = document.querySelectorAll('[data-muncul], [data-stagger]');

const tampilkan = (el) => {
    el.classList.add('tampak');
    el.querySelectorAll('[data-hitung]').forEach(hitung);
    if (el.matches('[data-hitung]')) hitung(el);
};

if ('IntersectionObserver' in window && !kurangiGerak) {
    const pengamat = new IntersectionObserver(
        (entri) => {
            entri.forEach((e) => {
                if (e.isIntersecting) {
                    tampilkan(e.target);
                    pengamat.unobserve(e.target);
                }
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.12 },
    );
    bagian.forEach((el) => {
        // Bagian yang sudah terlihat saat halaman dibuka langsung tampil, tanpa menunggu pengamat.
        if (el.getBoundingClientRect().top < window.innerHeight * 0.9) {
            tampilkan(el);
        } else {
            pengamat.observe(el);
        }
    });
} else {
    bagian.forEach((el) => el.classList.add('tampak'));
}

// ---------- Angka berhitung naik ("3.412", "642,5") ----------
// Angka asli sudah tertulis di HTML, jadi tanpa JavaScript atau dengan "kurangi gerakan" angka tetap benar.
function hitung(el) {
    if (kurangiGerak || el.dataset.sudahDihitung) return;
    el.dataset.sudahDihitung = '1';

    const asli = el.textContent.trim();
    const nilai = Number(asli.replace(/\./g, '').replace(',', '.'));
    if (!Number.isFinite(nilai) || nilai <= 0) return;

    const desimal = asli.includes(',') ? asli.split(',')[1].length : 0;
    const format = new Intl.NumberFormat('id-ID', { minimumFractionDigits: desimal, maximumFractionDigits: desimal });
    const mulai = performance.now();
    const lama = 1100;

    const langkah = (kini) => {
        const t = Math.min(1, (kini - mulai) / lama);
        const halus = 1 - Math.pow(1 - t, 3); // power2/3.out: cepat di awal, melambat di akhir
        el.textContent = format.format(nilai * halus);
        if (t < 1) requestAnimationFrame(langkah);
        else el.textContent = asli;
    };
    requestAnimationFrame(langkah);
}

// ---------- Menu loncat (Profil Desa): bagian yang sedang dibaca ikut menyala ----------
document.querySelectorAll('[data-sorot-bagian]').forEach((nav) => {
    const tautan = [...nav.querySelectorAll('a[href^="#"]')];
    const bagianTujuan = tautan.map((a) => document.querySelector(a.getAttribute('href'))).filter(Boolean);
    if (!('IntersectionObserver' in window) || bagianTujuan.length === 0) return;

    const nyalakan = (id) => tautan.forEach((a) => a.setAttribute('aria-current', String(a.getAttribute('href') === `#${id}`)));
    const pengamat = new IntersectionObserver(
        (entri) => {
            const terlihat = entri.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
            if (terlihat[0]) nyalakan(terlihat[0].target.id);
        },
        // Bagian dianggap "sedang dibaca" saat melewati sepertiga atas layar.
        { rootMargin: '-30% 0px -60% 0px' },
    );
    bagianTujuan.forEach((b) => pengamat.observe(b));
});

// ---------- Header transparan berubah putih padat setelah digulir (semua halaman publik) ----------
const header = document.querySelector('[data-header-transparan]');
if (header) {
    const perbarui = () => header.toggleAttribute('data-padat', window.scrollY > 24);
    perbarui();
    window.addEventListener('scroll', perbarui, { passive: true });
}

// ---------- Tombol bulat "kembali ke atas" (pojok kanan bawah) ----------
// Muncul setelah digulir lebih dari 400px; cincin terisi sesuai jauhnya gulir.
const keAtas = document.querySelector('[data-ke-atas]');
if (keAtas) {
    const cincin = keAtas.querySelector('[data-ke-atas-cincin]');
    const keliling = 2 * Math.PI * 25;
    let tunggu = 0;

    const perbaruiKeAtas = () => {
        tunggu = 0;
        const y = window.scrollY;
        const maks = document.documentElement.scrollHeight - window.innerHeight;
        keAtas.toggleAttribute('data-tampil', y > 400);
        if (cincin && maks > 0) cincin.style.strokeDashoffset = String(keliling * (1 - Math.min(1, y / maks)));
    };

    window.addEventListener('scroll', () => {
        if (!tunggu) tunggu = requestAnimationFrame(perbaruiKeAtas);
    }, { passive: true });
    perbaruiKeAtas();

    keAtas.addEventListener('click', (e) => {
        window.scrollTo({ top: 0, behavior: kurangiGerak ? 'auto' : 'smooth' });
        // Hanya saat ditekan lewat keyboard (detail 0): fokus dibawa ke awal halaman, bukan tertinggal
        // di tombol yang menghilang. Klik tetikus tidak memunculkan tautan "Lompat ke isi".
        const awal = document.querySelector('a[href="#isi"]');
        if (e.detail === 0 && awal) awal.focus({ preventScroll: true });
    });
}

// ---------- Pagination di browser: [data-paginasi="N"] = N butir per halaman ----------
// Dipakai untuk daftar yang sudah lengkap di halaman (produk toko, tabel panen, log EWS, daftar admin).
// Tanpa JavaScript semua butir tetap tampil. Daftar yang isinya diganti skrip lain (log EWS) ikut diperbarui.
const PANAH_KIRI = '<svg class="size-4" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
const PANAH_KANAN = '<svg class="size-4" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

// Nomor yang ditampilkan: 1, …, sekitar halaman aktif, …, terakhir.
function nomorHalaman(aktif, total) {
    const nomor = new Set([1, total, aktif - 1, aktif, aktif + 1]);
    const urut = [...nomor].filter((n) => n >= 1 && n <= total).sort((a, b) => a - b);
    return urut.flatMap((n, i) => (i > 0 && n - urut[i - 1] > 1 ? ['…', n] : [n]));
}

function pasangPaginasi(wadah) {
    const per = Math.max(1, Number(wadah.dataset.paginasi) || 10);
    const jangkar = wadah.tagName === 'TBODY' ? wadah.closest('table') : wadah;
    const nav = document.createElement('nav');
    nav.className = 'paginasi';
    nav.setAttribute('aria-label', 'Halaman daftar');
    jangkar.after(nav);
    let aktif = 1;

    const gambar = () => {
        const butir = [...wadah.children];
        const total = Math.ceil(butir.length / per);
        if (total <= 1) {
            butir.forEach((el) => (el.hidden = false));
            nav.hidden = true;
            return;
        }
        aktif = Math.min(aktif, total);
        butir.forEach((el, i) => (el.hidden = Math.floor(i / per) + 1 !== aktif));
        nav.hidden = false;
        nav.innerHTML =
            `<button type="button" data-ke="${aktif - 1}" ${aktif === 1 ? 'disabled' : ''} class="paginasi__tombol">${PANAH_KIRI}<span class="sr-only sm:not-sr-only">Sebelumnya</span></button>` +
            `<span class="paginasi__nomor">${nomorHalaman(aktif, total)
                .map((n) => (n === '…'
                    ? '<span class="paginasi__elipsis" aria-hidden="true">…</span>'
                    : `<button type="button" data-ke="${n}" class="paginasi__tombol paginasi__tombol--nomor" ${n === aktif ? 'aria-current="page"' : ''} aria-label="Halaman ${n}">${n}</button>`))
                .join('')}</span>` +
            `<button type="button" data-ke="${aktif + 1}" ${aktif === total ? 'disabled' : ''} class="paginasi__tombol"><span class="sr-only sm:not-sr-only">Berikutnya</span>${PANAH_KANAN}</button>` +
            `<span class="paginasi__ket" aria-live="polite">Halaman ${aktif} dari ${total}</span>`;
    };

    nav.addEventListener('click', (e) => {
        const tombol = e.target.closest('button[data-ke]');
        if (!tombol || tombol.disabled) return;
        aktif = Number(tombol.dataset.ke);
        gambar();
        // Bawa pembaca ke awal daftar bila awal daftar sudah tergulir ke luar layar.
        const atas = jangkar.getBoundingClientRect().top;
        if (atas < 80) window.scrollBy({ top: atas - 110, behavior: kurangiGerak ? 'auto' : 'smooth' });
    });

    new MutationObserver(gambar).observe(wadah, { childList: true });
    gambar();
}

document.querySelectorAll('[data-paginasi]').forEach(pasangPaginasi);

// ---------- Tombol "Bagikan" di kartu status longsor (components/kartu-status) ----------
// HP: membuka lembar bagikan bawaan (WhatsApp, dll.). Browser tanpa fitur itu: teks + tautan disalin.
// Teks diambil dari kartu saat tombol ditekan, jadi selalu status terbaru (dasbor memperbaruinya berkala).
document.querySelectorAll('[data-bagikan-status]').forEach((tombol) => {
    const bisaBagikan = typeof navigator.share === 'function';
    const bisaSalin = Boolean(navigator.clipboard?.writeText);
    if (!bisaBagikan && !bisaSalin) return;
    tombol.hidden = false;

    const label = tombol.querySelector('[data-bagikan-teks]');
    const kartu = tombol.closest('.kartu-status');
    let kembalikan = 0;

    const kabari = (pesan) => {
        label.textContent = pesan;
        clearTimeout(kembalikan);
        kembalikan = setTimeout(() => (label.textContent = 'Bagikan'), 2500);
    };

    tombol.addEventListener('click', async () => {
        const kata = kartu?.querySelector('.kartu-status__kata')?.textContent.trim() ?? '';
        const saran = kartu?.querySelector('.kartu-status__saran')?.textContent.trim() ?? '';
        const teks = `Status longsor Desa Lumeneng saat ini: ${kata}. ${saran}`;
        const url = tombol.dataset.bagikanStatus;

        if (bisaBagikan) {
            try {
                await navigator.share({ title: 'Status Longsor Desa Lumeneng', text: teks, url });
                return;
            } catch (galat) {
                // Warga menutup lembar bagikan: bukan kesalahan, tidak perlu pesan apa pun.
                if (galat.name === 'AbortError') return;
                if (!bisaSalin) return kabari('Gagal membagikan');
            }
        }

        try {
            await navigator.clipboard.writeText(`${teks} ${url}`);
            kabari('Tautan disalin');
        } catch {
            kabari('Gagal menyalin');
        }
    });
});

// ---------- Tutup menu HP setelah salah satu tautan diketuk ----------
document.querySelectorAll('details[data-menu-hp]').forEach((detail) => {
    detail.querySelectorAll('nav a').forEach((a) => a.addEventListener('click', () => detail.removeAttribute('open')));
});
