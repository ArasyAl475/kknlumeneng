// Tab pengelompokan dan pengurutan rincian panen kopi.
// Tanpa JavaScript semua rincian tetap terlihat (lihat <noscript> di toko/hasil.blade.php).
export function pasangRincian() {
    const daftarTab = [...document.querySelectorAll('[role="tab"]')];
    if (!daftarTab.length) return;

    function pilih(tab, fokus = false) {
        daftarTab.forEach((t) => {
            const aktif = t === tab;
            t.setAttribute('aria-selected', String(aktif));
            t.tabIndex = aktif ? 0 : -1;
            document.getElementById(t.getAttribute('aria-controls')).classList.toggle('hidden', !aktif);
        });
        if (fokus) tab.focus();
    }

    daftarTab.forEach((tab, i) => {
        tab.addEventListener('click', () => pilih(tab));
        tab.addEventListener('keydown', (e) => {
            const arah = { ArrowRight: 1, ArrowLeft: -1 }[e.key];
            if (!arah) return;
            e.preventDefault();
            pilih(daftarTab[(i + arah + daftarTab.length) % daftarTab.length], true);
        });
    });

    pasangUrut();
}

const PEMBANDING = {
    awal: (a, b) => a.dataset.awal - b.dataset.awal,
    label: (a, b) => a.dataset.label.localeCompare(b.dataset.label, 'id'),
    panen: (a, b) => a.dataset.panen - b.dataset.panen,
    limbah: (a, b) => a.dataset.limbah - b.dataset.limbah,
};

// Satu keadaan urutan untuk semua tabel, supaya pilihan tidak hilang saat pindah tab.
function pasangUrut() {
    const tabel = [...document.querySelectorAll('[data-tabel-rincian]')];
    const pilih = document.querySelector('[data-urut-pilih]');
    const select = pilih?.querySelector('select');
    pilih?.removeAttribute('hidden');

    function terapkan(kolom, naik) {
        tabel.forEach((t) => {
            const isi = t.tBodies[0];
            const baris = [...isi.rows].sort((a, b) => (naik ? 1 : -1) * PEMBANDING[kolom](a, b));
            baris.forEach((b) => isi.appendChild(b));
            t.querySelectorAll('th[data-kolom]').forEach((th) => {
                th.setAttribute('aria-sort', th.dataset.kolom === kolom ? (naik ? 'ascending' : 'descending') : 'none');
            });
        });
        if (select) select.value = kolom === 'awal' ? 'awal' : `${kolom}-${naik ? 'naik' : 'turun'}`;
    }

    // Klik pertama pada kolom angka mengurutkan dari terbesar; klik lagi membalik.
    tabel.forEach((t) => t.querySelectorAll('[data-urut]').forEach((tombol) => {
        tombol.addEventListener('click', () => {
            const kolom = tombol.dataset.urut;
            const saatIni = tombol.closest('th').getAttribute('aria-sort');
            const awalNaik = kolom === 'label';
            terapkan(kolom, saatIni === 'none' ? awalNaik : saatIni !== 'ascending');
        });
    }));

    select?.addEventListener('change', () => {
        if (select.value === 'awal') return terapkan('awal', true);
        const [kolom, arah] = select.value.split('-');
        terapkan(kolom, arah === 'naik');
    });
}
