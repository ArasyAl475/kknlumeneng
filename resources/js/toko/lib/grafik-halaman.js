import { SERI, bangunTabel, catatan, deskripsi, renderRingkas, titikBerdata, urutanAktif } from './grafik-data.js';

// Lebar minimal per titik waktu; bila grafik lebih lebar dari layar, areanya bisa digeser ke samping.
const LEBAR_HARI = 12;
const LEBAR_PER_SERI = 14;

// Menunggu huruf siap agar teks di kanvas tidak memakai huruf cadangan, tetapi tidak menahan grafik lama-lama.
const tungguHuruf = () => Promise.race([document.fonts?.ready, new Promise((selesai) => setTimeout(selesai, 800))]);

export function pasangGrafik(akar) {
    const data = JSON.parse(akar.dataset.data);
    const cari = (pilihan) => akar.querySelector(pilihan);
    const el = {
        kontrol: cari('[data-kontrol]'),
        ringkas: cari('[data-ringkas]'),
        catatan: cari('[data-catatan]'),
        muat: cari('[data-muat]'),
        galat: cari('[data-galat]'),
        tanpaData: cari('[data-tanpa-data]'),
        deskripsi: cari('[data-deskripsi]'),
        bagianRupiah: cari('[data-bagian-rupiah]'),
        tombolTabel: cari('[data-tombol-tabel]'),
        teksTabel: cari('[data-teks-tabel]'),
        tabel: cari('[data-tabel]'),
        gulir: { kg: cari('[data-gulir="kg"]'), rupiah: cari('[data-gulir="rupiah"]') },
        wadah: { kg: cari('[data-kanvas-wadah="kg"]'), rupiah: cari('[data-kanvas-wadah="rupiah"]') },
        kanvas: { kg: cari('[data-kanvas="kg"]'), rupiah: cari('[data-kanvas="rupiah"]') },
    };
    const tombolSkala = [...akar.querySelectorAll('[data-skala]')];
    const tombolSeri = [...akar.querySelectorAll('[data-seri]')];

    const keadaan = {
        skala: 'bulan',
        aktif: new Set(tombolSeri.filter((t) => t.getAttribute('aria-pressed') === 'true').map((t) => t.dataset.seri)),
    };
    let mesin = null;
    let nomorGambar = 0;

    el.kontrol.hidden = false;
    el.tombolTabel.hidden = false;

    async function muatMesin() {
        // Dimuat saat pertama dibutuhkan: angka dan tabel sudah tampil sebelum Chart.js selesai diunduh.
        mesin ??= (await import('./grafik-kanvas.js')).buatKanvas();
        return mesin;
    }

    function lebarWadah(nama, jumlahTitik, jumlahSeri) {
        const perTitik = keadaan.skala === 'hari' ? LEBAR_HARI : Math.max(34, LEBAR_PER_SERI * jumlahSeri + 10);
        const perlu = jumlahTitik * perTitik;
        const tersedia = el.gulir[nama].clientWidth;
        el.wadah[nama].style.width = perlu > tersedia ? `${perlu}px` : '100%';
    }

    function segarkanTeks(skalaData) {
        renderRingkas(el.ringkas, skalaData, keadaan.aktif, keadaan.skala);
        const kalimat = catatan(skalaData, keadaan.aktif, keadaan.skala);
        el.catatan.textContent = kalimat;
        el.catatan.hidden = !kalimat;
        el.deskripsi.textContent = deskripsi(skalaData, keadaan.aktif);
        if (el.tombolTabel.getAttribute('aria-expanded') === 'true') {
            el.tabel.replaceChildren(bangunTabel(skalaData, keadaan.aktif, keadaan.skala));
        }
    }

    async function perbarui() {
        const skalaData = data[keadaan.skala];
        const seri = urutanAktif(keadaan.aktif);
        const adaKg = seri.some((s) => SERI[s].kanvas === 'kg' && skalaData[s].some((n) => n > 0));
        const adaRupiah = keadaan.aktif.has('pendapatan') && skalaData.pendapatan.some((n) => n > 0);

        tombolSkala.forEach((t) => t.setAttribute('aria-pressed', String(t.dataset.skala === keadaan.skala)));
        tombolSeri.forEach((t) => t.setAttribute('aria-pressed', String(keadaan.aktif.has(t.dataset.seri))));
        segarkanTeks(skalaData);

        // Jangan menggambar grafik datar penuh angka 0: beri tahu apa yang bisa dilakukan.
        const kosong = !adaKg && !adaRupiah;
        el.tanpaData.hidden = !kosong;
        if (kosong) {
            el.tanpaData.querySelector('[data-tanpa-judul]').textContent = seri.length
                ? 'Belum ada catatan untuk pilihan ini'
                : 'Belum ada data yang dipilih';
            el.tanpaData.querySelector('[data-tanpa-ket]').textContent = seri.length
                ? 'Coba aktifkan data lain atau ganti skala waktu.'
                : 'Pilih minimal satu data di atas.';
        }
        el.gulir.kg.hidden = !adaKg;
        el.bagianRupiah.hidden = !adaRupiah;
        el.galat.hidden = true;

        const giliran = ++nomorGambar;
        try {
            const kanvas = await muatMesin();
            await tungguHuruf();
            if (giliran !== nomorGambar) return;

            if (adaKg) {
                lebarWadah('kg', skalaData.label.length, seri.filter((s) => SERI[s].kanvas === 'kg').length);
                kanvas.gambar('kg', el.kanvas.kg, keadaan.skala, skalaData, keadaan.aktif);
            } else {
                kanvas.hancurkan('kg');
            }
            if (adaRupiah) {
                lebarWadah('rupiah', skalaData.label.length, 1);
                kanvas.gambar('rupiah', el.kanvas.rupiah, keadaan.skala, skalaData, keadaan.aktif);
            } else {
                kanvas.hancurkan('rupiah');
            }
            // Data terbaru ada di kanan, jadi mulai dari sana.
            Object.values(el.gulir).forEach((g) => { g.scrollLeft = g.scrollWidth; });
            el.muat.hidden = true;
        } catch {
            if (giliran !== nomorGambar) return;
            mesin = null;
            el.muat.hidden = true;
            el.galat.hidden = false;
        }
    }

    tombolSkala.forEach((t) => t.addEventListener('click', () => {
        keadaan.skala = t.dataset.skala;
        perbarui();
    }));
    tombolSeri.forEach((t) => t.addEventListener('click', () => {
        const seri = t.dataset.seri;
        if (keadaan.aktif.has(seri)) keadaan.aktif.delete(seri);
        else keadaan.aktif.add(seri);
        perbarui();
    }));

    akar.querySelector('[data-coba-lagi]').addEventListener('click', () => {
        el.muat.hidden = false;
        perbarui();
    });

    el.tombolTabel.addEventListener('click', () => {
        const buka = el.tombolTabel.getAttribute('aria-expanded') !== 'true';
        el.tombolTabel.setAttribute('aria-expanded', String(buka));
        el.teksTabel.textContent = buka ? 'Sembunyikan tabel' : 'Lihat sebagai tabel';
        el.tabel.hidden = !buka;
        if (buka) el.tabel.replaceChildren(bangunTabel(data[keadaan.skala], keadaan.aktif, keadaan.skala));
    });

    // Dua grafik (kg dan rupiah) digeser bersamaan agar waktunya tetap sejajar.
    let sinkron = false;
    const gulirKembar = [el.gulir.kg, el.gulir.rupiah];
    gulirKembar.forEach((g, i) => g.addEventListener('scroll', () => {
        if (sinkron) return;
        sinkron = true;
        gulirKembar[1 - i].scrollLeft = g.scrollLeft;
        requestAnimationFrame(() => { sinkron = false; });
    }, { passive: true }));

    // Lebar kanvas bergantung pada lebar layar (mis. HP diputar), jadi gambar ulang setelah ukuran stabil.
    let tunda;
    let lebarTerakhir = window.innerWidth;
    window.addEventListener('resize', () => {
        if (window.innerWidth === lebarTerakhir) return;
        lebarTerakhir = window.innerWidth;
        clearTimeout(tunda);
        tunda = setTimeout(perbarui, 200);
    });

    perbarui();
}

// Grafik perbandingan baglog per bulan. Tabelnya sudah ada di halaman (tanpa JavaScript), jadi bila
// Chart.js gagal diunduh, kanvasnya cukup disembunyikan.
export async function pasangGrafikBaglog(akar) {
    const data = JSON.parse(akar.dataset.data);
    const gulir = akar.querySelector('[data-gulir]');
    const wadah = akar.querySelector('[data-kanvas-wadah]');
    const kanvas = akar.querySelector('canvas');
    const muat = akar.querySelector('[data-muat]');
    const galat = akar.querySelector('[data-galat]');
    let grafik = null;

    async function gambar() {
        muat.hidden = false;
        galat.hidden = true;
        try {
            const modul = await import('./grafik-kanvas.js');
            await tungguHuruf();
            const perlu = data.label.length * 56;
            wadah.style.width = perlu > gulir.clientWidth ? `${perlu}px` : '100%';
            grafik?.destroy();
            grafik = modul.gambarBaglog(kanvas, data);
            gulir.scrollLeft = gulir.scrollWidth;
        } catch {
            galat.hidden = false;
        }
        muat.hidden = true;
    }

    akar.querySelector('[data-coba-lagi]').addEventListener('click', gambar);
    gambar();
}
