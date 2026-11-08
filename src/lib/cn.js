// Menggabungkan nama kelas, melewati nilai kosong: cn('a', kondisi && 'b') => 'a b'
export const cn = (...kelas) => kelas.filter(Boolean).join(' ');
