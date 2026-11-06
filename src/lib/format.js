// Format angka Indonesia, sama dengan App\Services\Toko\FormatHasil di sisi Laravel:
// koma untuk desimal, titik untuk ribuan.
const angka = (nilai, desimal) =>
    new Intl.NumberFormat('id-ID', { minimumFractionDigits: desimal, maximumFractionDigits: desimal }).format(nilai);

export const formatAngka = (nilai, desimal = 2) => angka(nilai, desimal);
export const formatKg = (nilai) => `${angka(nilai, 2)} kg`;
export const formatRupiah = (nilai) => `Rp ${angka(nilai, 0)}`;
export const formatPersen = (nilai) => `${angka(nilai, 1)}%`;
