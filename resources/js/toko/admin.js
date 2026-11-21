import './unggah-foto.js';
import { pasangSegarOtomatis } from './lib/segar-otomatis.js';

// Konfirmasi sebelum menghapus. Tanpa JavaScript, aksi tetap berjalan dan tercatat di log aktivitas.
// Dipasang di dokumen (bukan per formulir) karena daftar bisa diganti pembaruan otomatis.
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.matches?.('form[data-konfirmasi]') && !window.confirm(form.dataset.konfirmasi)) e.preventDefault();
});

// Daftar panen/penjualan ikut berubah sendiri saat data diubah lewat Google Sheets atau admin lain.
pasangSegarOtomatis();

// Setelah simpan gagal, langsung arahkan ke isian pertama yang salah agar admin tidak mencari sendiri.
// Tanpa JavaScript, kotak alert di atas halaman tetap menjelaskan apa yang perlu dibetulkan.
const isianSalah = document.querySelector('form [aria-invalid="true"]');
if (isianSalah) {
    isianSalah.focus({ preventScroll: true });
    isianSalah.scrollIntoView({ block: 'center' });
}

// Tombol "Tambah baris" hanya muncul bila JavaScript jalan; tanpa itu form sudah menyediakan baris kosong.
document.querySelectorAll('[data-tambah-baris]').forEach((tombol) => {
    const jenis = tombol.dataset.tambahBaris;
    const daftar = document.querySelector(`[data-daftar-baris="${jenis}"]`);
    const cetakan = document.querySelector(`template[data-cetakan="${jenis}"]`);
    if (!daftar || !cetakan) return;

    const MAKS = 20;
    const tampilkanTombolHapus = () => daftar.querySelectorAll('[data-hapus-baris]').forEach((b) => { b.hidden = false; });
    const perbaruiTombolTambah = () => { tombol.hidden = daftar.children.length >= MAKS; };

    // Nomor baris baru diambil dari nomor terbesar yang ada, bukan jumlah baris: setelah ada baris dihapus,
    // memakai jumlah baris bisa membuat dua baris bernomor sama sehingga salah satunya hilang saat disimpan.
    let berikut = 0;
    daftar.querySelectorAll('[name]').forEach((isian) => {
        const nomor = Number(isian.name.match(/\[(\d+)\]/)?.[1] ?? -1);
        berikut = Math.max(berikut, nomor + 1);
    });

    tampilkanTombolHapus();
    perbaruiTombolTambah();

    tombol.addEventListener('click', () => {
        if (daftar.children.length >= MAKS) return;
        daftar.insertAdjacentHTML('beforeend', cetakan.innerHTML.replaceAll('__i__', String(berikut++)));
        tampilkanTombolHapus();
        daftar.lastElementChild?.querySelector('input')?.focus();
        perbaruiTombolTambah();
    });

    daftar.addEventListener('click', (e) => {
        const hapus = e.target.closest('[data-hapus-baris]');
        if (!hapus) return;
        const baris = hapus.closest('fieldset');
        // Fokus dipindah ke baris berikutnya (atau tombol Tambah) agar pengguna pembaca layar tidak "tersesat".
        const tujuan = baris.nextElementSibling?.querySelector('input') ?? tombol;
        baris.remove();
        perbaruiTombolTambah();
        tujuan.focus();
    });
});
