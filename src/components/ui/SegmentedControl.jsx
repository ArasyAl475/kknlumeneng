import { cn } from '../../lib/cn.js';
import Icon from './Icon.jsx';

const WADAH = {
    // Pilihan waktu di kepala halaman: putih bergaris tepi, pilihan aktif hijau hutan.
    solid: 'bg-surface-container-lowest border border-outline-variant/60 shadow-sm',
    // Pilihan di dalam kartu: latar abu hijau, pilihan aktif putih.
    subtle: 'bg-surface-container',
};

const AKTIF = {
    solid: 'bg-primary-container text-on-primary font-semibold',
    subtle: 'bg-surface-container-lowest text-primary font-bold shadow-sm',
};

/** Kontrol segmen (tab pilihan tunggal). Terkendali: `value` dan `onChange` datang dari induk. */
export default function SegmentedControl({ options, value, onChange, label, variant = 'solid', className }) {
    return (
        <div role="group" aria-label={label} className={cn('inline-flex max-w-full overflow-x-auto rounded-lg p-1 text-label-md font-label-md', WADAH[variant], className)}>
            {options.map((opsi) => {
                const aktif = opsi.value === value;

                return (
                    <button
                        key={opsi.value}
                        type="button"
                        aria-pressed={aktif}
                        onClick={() => onChange?.(opsi.value)}
                        className={cn(
                            'flex min-h-10 items-center gap-1 whitespace-nowrap rounded px-3 py-1 transition-colors duration-150',
                            aktif ? AKTIF[variant] : 'text-on-surface-variant hover:text-on-surface',
                        )}
                    >
                        {opsi.icon && <Icon name={opsi.icon} size={16} />}
                        {opsi.label}
                    </button>
                );
            })}
        </div>
    );
}
