import { Icon } from '../ui/index.js';

/** Kepala halaman (bukan card): label kecil, judul, penjelasan, dan slot kontrol di kanan (mis. pilihan waktu). */
export default function PageHeader({ id, label, ikonLabel = 'verified', judul, deskripsi, children }) {
    return (
        <section id={id} className="flex flex-col justify-between gap-6 pb-2 lg:flex-row lg:items-end">
            <div className="max-w-2xl space-y-1.5">
                {label && (
                    <div className="flex items-center gap-2 text-label-md font-label-md text-secondary">
                        <Icon name={ikonLabel} size={18} />
                        <span>{label}</span>
                    </div>
                )}
                <h1 className="text-headline-lg font-headline-lg tracking-tight text-on-surface">{judul}</h1>
                {deskripsi && <p className="text-body-md font-body-md text-on-surface-variant">{deskripsi}</p>}
            </div>
            {children && <div className="flex shrink-0 flex-col items-start gap-3 sm:flex-row sm:flex-wrap sm:items-center">{children}</div>}
        </section>
    );
}
