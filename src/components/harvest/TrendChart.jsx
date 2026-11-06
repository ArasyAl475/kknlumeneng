import { cn } from '../../lib/cn.js';

/**
 * Grafik batang berkelompok tanpa pustaka (hanya CSS), ringan di sinyal lemah.
 * Tanpa kotak pembungkus: area batang hanya diberi garis dasar tipis, karena sudah berada di dalam card.
 * Props:
 *   labels  ['Mei', 'Jun', ...]
 *   seri    [{ kunci, nama, nilai: [..], kelas: 'bg-primary' }]
 *   satuan  'kg'
 *   sorot   indeks label yang ditebalkan (mis. puncak)
 *   format  (angka) => teks, dipakai pada petunjuk dan tabel pembaca layar
 * Warna tidak berdiri sendiri: nama seri ada di legenda dan petunjuk nilai muncul saat disorot.
 * Tabel tersembunyi (sr-only) memberi pembaca layar angka yang sama dengan grafik.
 */
export default function TrendChart({ labels, seri, satuan = 'kg', sorot, format = (n) => String(n) }) {
    const maks = Math.max(1, ...seri.flatMap((s) => s.nilai));

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2 text-body-sm font-body-sm text-on-surface-variant">
                <span className="font-medium">Komparasi volume panen bulanan ({satuan})</span>
                <ul className="flex items-center gap-4 text-label-sm font-label-sm">
                    {seri.map((s) => (
                        <li key={s.kunci} className="flex items-center gap-1.5">
                            <span aria-hidden="true" className={cn('h-3 w-3 rounded-sm', s.kelas)} />
                            {s.nama}
                        </li>
                    ))}
                </ul>
            </div>

            <div role="img" aria-label={`Grafik batang ${seri.map((s) => s.nama).join(' dan ')} per bulan`} className="flex h-64 items-end justify-between gap-3 border-b border-outline-variant/40 pt-4">
                {labels.map((label, i) => (
                    <div key={label} className="group flex h-full flex-1 flex-col items-center justify-end">
                        <div className="flex h-44 w-full items-end justify-center gap-1.5">
                            {seri.map((s) => (
                                <div
                                    key={s.kunci}
                                    title={`${s.nama}: ${format(s.nilai[i])} ${satuan}`}
                                    className={cn('relative w-1/2 max-w-[20px] rounded-t transition-all group-hover:brightness-110', s.kelas)}
                                    style={{ height: `${(s.nilai[i] / maks) * 100}%` }}
                                >
                                    <span className="absolute -top-6 left-1/2 hidden -translate-x-1/2 whitespace-nowrap rounded bg-primary px-1 text-label-sm font-label-sm text-on-primary shadow group-hover:block">
                                        {format(s.nilai[i])}
                                    </span>
                                </div>
                            ))}
                        </div>
                        <span className={cn('my-2 text-label-sm font-label-sm', i === sorot ? 'font-bold text-primary' : 'font-medium text-on-surface-variant')}>{label}</span>
                    </div>
                ))}
            </div>

            <table className="sr-only">
                <caption>Data grafik per bulan</caption>
                <thead>
                    <tr>
                        <th scope="col">Bulan</th>
                        {seri.map((s) => (
                            <th key={s.kunci} scope="col">
                                {s.nama} ({satuan})
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {labels.map((label, i) => (
                        <tr key={label}>
                            <th scope="row">{label}</th>
                            {seri.map((s) => (
                                <td key={s.kunci}>{format(s.nilai[i])}</td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
