// Model 3D peta desa di beranda: bisa diputar dengan jari/mouse, diperbesar dengan tombol atau cubitan.
// Ini gambar tiga dimensi dari kajian desa yang dimiringkan dengan CSS 3D, BUKAN model dengan data ketinggian
// asli. Sengaja tanpa pustaka tambahan (Three.js dll.) supaya halaman tetap ringan di sinyal lemah.
// Markup ada di resources/views/landing/index.blade.php (atribut data-bentang3d).

const wadah = document.querySelector('[data-bentang3d]');
if (wadah) mulai(wadah);

function mulai(wadah) {
    const panggung = wadah.querySelector('[data-b3d-panggung]');
    const model = wadah.querySelector('[data-b3d-model]');
    const petunjuk = wadah.querySelector('[data-b3d-petunjuk]');
    const kendali = wadah.querySelector('[data-b3d-kendali]');
    const info = wadah.querySelector('[data-b3d-info]');
    const gerakHalus = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Batas putar (derajat) dan perbesaran. Batas sengaja sempit agar gambar datar tidak terlihat menipis.
    const BATAS = { ry: 55, rx: 28, zMin: 1, zMax: 2.2 };
    const sasaran = { ry: 0, rx: 0, z: 1 };
    const kini = { ry: 0, rx: 0, z: 1 };

    let bingkai = 0;
    let sudahDisentuh = false;
    const jari = new Map();
    let jarakAwal = 0;
    let zoomAwal = 1;

    const jepit = (nilai, min, maks) => Math.min(maks, Math.max(min, nilai));

    function gambar() {
        const lebar = panggung.clientWidth;
        const tinggi = panggung.clientHeight;
        // Saat diperbesar, memutar juga menggeser gambar sehingga bagian tepi bisa dilihat tanpa gerakan geser terpisah.
        const geserX = -(kini.ry / BATAS.ry) * (kini.z - 1) * lebar * 0.3;
        const geserY = (kini.rx / BATAS.rx) * (kini.z - 1) * tinggi * 0.25;
        model.style.transform = `translate3d(${geserX.toFixed(1)}px, ${geserY.toFixed(1)}px, 0) rotateX(${kini.rx.toFixed(2)}deg) rotateY(${kini.ry.toFixed(2)}deg) scale(${kini.z.toFixed(3)})`;
    }

    function jalan() {
        bingkai = 0;
        let selesai = true;
        for (const k of ['ry', 'rx', 'z']) {
            const selisih = sasaran[k] - kini[k];
            if (!gerakHalus || Math.abs(selisih) < 0.01) {
                kini[k] = sasaran[k];
            } else {
                kini[k] += selisih * 0.18;
                selesai = false;
            }
        }
        gambar();
        if (!selesai) bingkai = requestAnimationFrame(jalan);
    }

    function perbarui() {
        sasaran.ry = jepit(sasaran.ry, -BATAS.ry, BATAS.ry);
        sasaran.rx = jepit(sasaran.rx, -BATAS.rx, BATAS.rx);
        sasaran.z = jepit(sasaran.z, BATAS.zMin, BATAS.zMax);
        if (!bingkai) bingkai = requestAnimationFrame(jalan);

        kendali?.querySelector('[data-aksi="besar"]')?.toggleAttribute('disabled', sasaran.z >= BATAS.zMax);
        kendali?.querySelector('[data-aksi="kecil"]')?.toggleAttribute('disabled', sasaran.z <= BATAS.zMin);
    }

    function disentuh() {
        if (sudahDisentuh) return;
        sudahDisentuh = true;
        petunjuk?.classList.add('opacity-0');
    }

    // ── Geser: putar. Di layar sentuh hanya geser mendatar yang memutar; geser tegak tetap menggulir halaman. ──
    panggung.addEventListener('pointerdown', (e) => {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        panggung.setPointerCapture(e.pointerId);
        jari.set(e.pointerId, { x: e.clientX, y: e.clientY });
        if (jari.size === 2) {
            const [a, b] = [...jari.values()];
            jarakAwal = Math.hypot(a.x - b.x, a.y - b.y);
            zoomAwal = sasaran.z;
        }
        disentuh();
    });

    panggung.addEventListener('pointermove', (e) => {
        const titik = jari.get(e.pointerId);
        if (!titik) return;

        if (jari.size === 2) {
            // Cubit dua jari: perbesar atau perkecil.
            titik.x = e.clientX;
            titik.y = e.clientY;
            const [a, b] = [...jari.values()];
            const jarak = Math.hypot(a.x - b.x, a.y - b.y);
            if (jarakAwal > 0) {
                sasaran.z = zoomAwal * (jarak / jarakAwal);
                perbarui();
            }
            return;
        }

        const dx = e.clientX - titik.x;
        const dy = e.clientY - titik.y;
        titik.x = e.clientX;
        titik.y = e.clientY;
        sasaran.ry += dx * 0.35;
        if (e.pointerType === 'mouse') sasaran.rx -= dy * 0.25;
        perbarui();
    });

    const lepas = (e) => {
        jari.delete(e.pointerId);
        if (jari.size < 2) jarakAwal = 0;
    };
    panggung.addEventListener('pointerup', lepas);
    panggung.addEventListener('pointercancel', lepas);

    function ubah(aksi) {
        disentuh();
        if (aksi === 'kiri') sasaran.ry -= 15;
        else if (aksi === 'kanan') sasaran.ry += 15;
        else if (aksi === 'atas') sasaran.rx -= 10;
        else if (aksi === 'bawah') sasaran.rx += 10;
        else if (aksi === 'besar') sasaran.z += 0.3;
        else if (aksi === 'kecil') sasaran.z -= 0.3;
        else if (aksi === 'ulang') {
            sasaran.ry = 0;
            sasaran.rx = 0;
            sasaran.z = 1;
        }
        perbarui();
        if (info && (aksi === 'besar' || aksi === 'kecil' || aksi === 'ulang')) {
            info.textContent = `Perbesaran ${Math.round(sasaran.z * 100)} persen`;
        }
    }

    kendali?.addEventListener('click', (e) => {
        const aksi = e.target.closest('[data-aksi]')?.dataset.aksi;
        if (aksi) ubah(aksi);
    });

    // Papan ketik: panah memutar, + dan - memperbesar/memperkecil, 0 mengatur ulang.
    const kunci = { ArrowLeft: 'kiri', ArrowRight: 'kanan', ArrowUp: 'atas', ArrowDown: 'bawah', '+': 'besar', '=': 'besar', '-': 'kecil', 0: 'ulang' };
    panggung.addEventListener('keydown', (e) => {
        const aksi = kunci[e.key];
        if (!aksi) return;
        e.preventDefault();
        ubah(aksi);
    });

    kendali?.removeAttribute('hidden');

    // Contoh gerakan sekali saat model pertama kali terlihat, supaya jelas bahwa gambar ini bisa diputar.
    // Berhenti begitu pengunjung menyentuhnya, dan dilewati bila pengunjung meminta gerak dikurangi.
    if (gerakHalus && 'IntersectionObserver' in window) {
        const pengamat = new IntersectionObserver(
            (daftar) => {
                if (!daftar[0].isIntersecting) return;
                pengamat.disconnect();
                [[16, 0], [-16, 750], [0, 1500]].forEach(([sudut, tunda]) =>
                    setTimeout(() => {
                        if (sudahDisentuh) return;
                        sasaran.ry = sudut;
                        perbarui();
                    }, tunda + 400),
                );
            },
            { threshold: 0.6 },
        );
        pengamat.observe(panggung);
    }

    window.addEventListener('resize', gambar, { passive: true });
    perbarui();
}
