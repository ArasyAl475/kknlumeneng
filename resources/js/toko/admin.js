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
    tombol.hidden = false;

    tombol.addEventListener('click', () => {
        const nomor = daftar.children.length;
        if (nomor >= MAKS) return;
        daftar.insertAdjacentHTML('beforeend', cetakan.innerHTML.replaceAll('__i__', String(nomor)));
        daftar.lastElementChild?.querySelector('input')?.focus();
        if (nomor + 1 >= MAKS) tombol.hidden = true;
    });
});
