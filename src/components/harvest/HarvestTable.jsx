import { cn } from '../../lib/cn.js';
import { Badge, Card, EmptyState } from '../ui/index.js';

const RATA = { kiri: 'text-left', kanan: 'text-right', tengah: 'text-center' };

/**
 * Tabel rincian generik di dalam satu card. Tabel tidak dibungkus kotak lagi: cukup garis pemisah baris.
 * Props:
 *   judul, deskripsi?, lencana?        kepala card
 *   kontrol? (node)                    slot di kanan kepala (filter, tombol export)
 *   kolom [{ kunci, label, rata?: 'kiri'|'kanan'|'tengah', render?: (baris) => node }]
 *   baris [{ ... }], kunciBaris        nama properti unik tiap baris
 *   kosong? { judul, deskripsi }       keadaan kosong bila baris tidak ada
 *   kaki? (node)                       slot di bawah tabel (keterangan jumlah, penomoran halaman)
 * Kolom pertama menjadi judul baris (<th scope="row">) agar terbaca benar oleh pembaca layar.
 */
export default function HarvestTable({ judul, deskripsi, lencana, kontrol, kolom, baris, kunciBaris = 'id', kosong, kaki, className }) {
    return (
        <Card padding="lg" className={cn('space-y-4', className)}>
            <div className="flex flex-col justify-between gap-4 pb-2 md:flex-row md:items-center">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="text-headline-md font-headline-md text-on-surface">{judul}</h2>
                        {lencana && <Badge pill>{lencana}</Badge>}
                    </div>
                    {deskripsi && <p className="text-body-sm font-body-sm text-on-surface-variant">{deskripsi}</p>}
                </div>
                {kontrol && <div className="flex flex-wrap items-center gap-3">{kontrol}</div>}
            </div>

            {baris.length === 0 ? (
                <EmptyState icon="coffee" title={kosong?.judul ?? 'Belum ada data'} description={kosong?.deskripsi} className="border-t border-outline-variant/30" />
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-left">
                        <thead>
                            <tr className="border-y border-outline-variant/40 bg-surface-container-low text-label-md font-label-md text-on-surface-variant">
                                {kolom.map((k) => (
                                    <th key={k.kunci} scope="col" className={cn('whitespace-nowrap px-4 py-3', RATA[k.rata ?? 'kiri'])}>
                                        {k.label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-outline-variant/30 border-b border-outline-variant/30 text-body-md font-body-md text-on-surface">
                            {baris.map((b) => (
                                <tr key={b[kunciBaris]} className="transition-colors hover:bg-surface-container-low/50">
                                    {kolom.map((k, i) => {
                                        const isi = k.render ? k.render(b) : b[k.kunci];
                                        const kelas = cn('px-4 py-3', RATA[k.rata ?? 'kiri']);

                                        return i === 0 ? (
                                            <th key={k.kunci} scope="row" className={cn(kelas, 'whitespace-nowrap font-medium text-on-surface-variant')}>
                                                {isi}
                                            </th>
                                        ) : (
                                            <td key={k.kunci} className={kelas}>
                                                {isi}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {kaki && <div className="flex flex-col items-center justify-between gap-3 pt-2 text-body-sm font-body-sm text-on-surface-variant sm:flex-row">{kaki}</div>}
        </Card>
    );
}
