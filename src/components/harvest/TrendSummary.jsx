import { cn } from '../../lib/cn.js';

/**
 * Tiga angka ringkas di bawah grafik. Datar: dipisah garis tegak tipis, bukan kotak sendiri-sendiri.
 * Props: item [{ label, nilai, satuan?, tone?: 'biasa' | 'positif' }]
 */
export default function TrendSummary({ item }) {
    return (
        <dl className="grid grid-cols-3 divide-x divide-outline-variant/40 border-t border-outline-variant/30 pt-4">
            {item.map((i) => (
                <div key={i.label} className="px-3 text-center first:pl-0 last:pr-0 sm:px-5 sm:text-left sm:first:pl-0">
                    <dt className="block text-body-sm font-body-sm text-on-surface-variant">{i.label}</dt>
                    <dd className={cn('text-headline-md font-headline-md', i.tone === 'positif' ? 'text-secondary' : 'text-on-surface')}>
                        {i.nilai}
                        {i.satuan && <span className="ml-1 text-body-sm font-body-sm font-normal text-outline">{i.satuan}</span>}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
