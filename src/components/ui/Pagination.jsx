import { cn } from '../../lib/cn.js';

const TOMBOL = 'rounded border border-outline-variant bg-surface-bright px-3 py-1 text-on-surface hover:bg-surface-container disabled:opacity-50';

/** Penomoran halaman sederhana. Terkendali: `halaman` (mulai 1) dan `onChange` datang dari induk. */
export default function Pagination({ halaman, jumlahHalaman, onChange, className }) {
    const nomor = Array.from({ length: jumlahHalaman }, (_, i) => i + 1);

    return (
        <nav aria-label="Halaman" className={cn('inline-flex items-center gap-1 text-body-sm font-body-sm', className)}>
            <button type="button" className={TOMBOL} disabled={halaman <= 1} onClick={() => onChange(halaman - 1)}>
                Sebelumnya
            </button>
            {nomor.map((n) =>
                n === halaman ? (
                    <span key={n} aria-current="page" className="rounded bg-primary px-3 py-1 text-label-sm font-label-sm font-bold text-on-primary">
                        {n}
                    </span>
                ) : (
                    <button key={n} type="button" className={TOMBOL} onClick={() => onChange(n)}>
                        {n}
                    </button>
                ),
            )}
            <button type="button" className={TOMBOL} disabled={halaman >= jumlahHalaman} onClick={() => onChange(halaman + 1)}>
                Berikutnya
            </button>
        </nav>
    );
}
