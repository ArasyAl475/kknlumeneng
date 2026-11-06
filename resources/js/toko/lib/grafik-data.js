// Bagian grafik yang TIDAK butuh Chart.js: format angka, ringkasan, deskripsi, dan tabel.
// Dipisah supaya angka tetap terbaca (lewat tabel) walau pustaka grafik gagal diunduh di sinyal lemah.
export const SERI = {
    kopi: { nama: 'Panen kopi', satuan: 'kg', kanvas: 'kg' },
    jamur: { nama: 'Panen jamur', satuan: 'kg', kanvas: 'kg' },
    terjual: { nama: 'Jamur terjual', satuan: 'kg', kanvas: 'kg' },
    pendapatan: { nama: 'Pendapatan', satuan: 'Rp', kanvas: 'rupiah' },
};

const NAMA_SKALA = { hari: 'hari', bulan: 'bulan', tahun: 'tahun' };
const BATAS = { hari: '90 hari', bulan: '36 bulan' };

// Sama dengan FormatHasil di sisi server: kg dua desimal, rupiah bulat.
export function formatNilai(seri, nilai) {
    if (SERI[seri].satuan === 'Rp') {
        return `Rp ${Math.round(nilai).toLocaleString('id-ID')}`;
    }
    return `${nilai.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} kg`;
}

export function urutanAktif(aktif) {
    return Object.keys(SERI).filter((k) => aktif.has(k));
}

export function ringkas(skalaData, seri) {
    const nilai = skalaData[seri];
    const total = nilai.reduce((a, b) => a + b, 0);
    let puncak = null;
    nilai.forEach((n, i) => {
        if (n > 0 && (puncak === null || n > puncak.nilai)) puncak = { nilai: n, label: skalaData.lengkap[i] };
    });

    return { total, rata: nilai.length ? total / nilai.length : 0, puncak };
}

// Berapa titik waktu yang benar-benar punya catatan pada data yang sedang dipilih.
export function titikBerdata(skalaData, aktif) {
    return skalaData.label.filter((_, i) => urutanAktif(aktif).some((s) => skalaData[s][i] > 0)).length;
}

function buat(tag, kelas, teks) {
    const el = document.createElement(tag);
    if (kelas) el.className = kelas;
    if (teks !== undefined) el.textContent = teks;
    return el;
}

function nilaiRingkas(label, isi) {
    const dd = buat('dd', 'ringkas__nilai', label);
    dd.append(' ', buat('strong', '', isi));
    return dd;
}

export function renderRingkas(dl, skalaData, aktif, skala) {
    dl.replaceChildren(...urutanAktif(aktif).map((seri) => {
        const r = ringkas(skalaData, seri);
        const baris = buat('div', 'ringkas__seri');
        const nama = buat('dt', 'ringkas__nama');
        // Ikon disalin dari pilihan grafik di halaman, jadi ikon di sini selalu sama dengan di sana.
        const ikon = document.querySelector(`[data-seri="${seri}"] .ikon-seri`)?.cloneNode(true);
        nama.append(...(ikon ? [ikon] : []), SERI[seri].nama);
        baris.append(nama);

        if (!r.puncak) {
            baris.append(buat('dd', 'ringkas__nilai', 'Belum ada catatan pada rentang ini.'));
            return baris;
        }
        baris.append(
            nilaiRingkas('Total', formatNilai(seri, r.total)),
            nilaiRingkas(`Rata-rata per ${NAMA_SKALA[skala]}`, formatNilai(seri, r.rata)),
            nilaiRingkas('Tertinggi', `${r.puncak.label}, ${formatNilai(seri, r.puncak.nilai)}`),
        );

        return baris;
    }));
}

// Catatan pendek bila grafik dipotong atau datanya terlalu sedikit untuk disebut tren.
export function catatan(skalaData, aktif, skala) {
    const n = titikBerdata(skalaData, aktif);
    const bagian = [];
    if (skalaData.terpotong) {
        bagian.push(`Menampilkan ${BATAS[skala]} terakhir saja. Pilih tanggal di Kustom untuk melihat yang lebih lama.`);
    }
    if (n > 0 && n <= 2) {
        bagian.push(`Baru ada ${n} ${NAMA_SKALA[skala]} yang punya catatan, jadi tampil sebagai batang tunggal dan belum bisa disebut tren. Coba skala lain.`);
    }

    return bagian.join(' ');
}

export function deskripsi(skalaData, aktif) {
    const kalimat = urutanAktif(aktif).map((seri) => {
        const { puncak } = ringkas(skalaData, seri);
        return puncak
            ? `${SERI[seri].nama} paling tinggi pada ${puncak.label}, ${formatNilai(seri, puncak.nilai)}.`
            : `${SERI[seri].nama} belum ada catatan pada rentang ini.`;
    });

    return kalimat.length ? kalimat.join(' ') : 'Belum ada data yang dipilih.';
}

// Tabel dengan data yang sama dengan grafik: bulan terbaru di atas, mudah dicari warga.
export function bangunTabel(skalaData, aktif, skala) {
    const seri = urutanAktif(aktif);
    const wadah = buat('div', 'grafik__tabel');
    const tabel = buat('table', 'tabel-data');
    tabel.append(buat('caption', '', `Data per ${NAMA_SKALA[skala]}, yang terbaru di atas.`));

    const kepala = buat('tr');
    kepala.append(Object.assign(buat('th', '', skala[0].toUpperCase() + skala.slice(1)), { scope: 'col' }));
    seri.forEach((s) => kepala.append(Object.assign(buat('th', '', SERI[s].nama), { scope: 'col' })));
    const thead = buat('thead');
    thead.append(kepala);
    tabel.append(thead);

    const isi = buat('tbody');
    for (let i = skalaData.label.length - 1; i >= 0; i--) {
        const baris = buat('tr');
        baris.append(Object.assign(buat('th', '', skalaData.lengkap[i]), { scope: 'row' }));
        seri.forEach((s) => baris.append(buat('td', skalaData[s][i] > 0 ? '' : 'redup', formatNilai(s, skalaData[s][i]))));
        isi.append(baris);
    }
    tabel.append(isi);
    wadah.append(tabel);

    return wadah;
}
