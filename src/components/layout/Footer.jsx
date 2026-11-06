/**
 * Footer ringkas. Pernyataan peringatan dini (`catatan`) wajib ada dan tidak dikecilkan:
 * informasi di situs adalah alat bantu, bukan peringatan resmi BPBD.
 * Props: nama, hakCipta, tautan [{ label, href }], catatan
 */
export default function Footer({ nama, hakCipta, tautan = [], catatan }) {
    return (
        <footer className="mt-12 border-t border-outline-variant bg-surface-container-high">
            <div className="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 sm:px-6 md:flex-row lg:px-12">
                <div className="space-y-1 text-center md:text-left">
                    <div className="text-headline-sm font-headline-sm text-on-surface">{nama}</div>
                    <p className="text-body-sm font-body-sm text-on-surface-variant">{hakCipta}</p>
                </div>
                <nav aria-label="Tautan footer" className="flex flex-wrap items-center justify-center gap-6 text-body-sm font-body-sm">
                    {tautan.map((t) => (
                        <a key={t.href} href={t.href} className="text-on-surface-variant transition-colors hover:text-primary">
                            {t.label}
                        </a>
                    ))}
                </nav>
            </div>
            {catatan && (
                <div className="border-t border-outline-variant/60">
                    <p className="mx-auto max-w-7xl px-4 py-4 text-body-md font-body-md text-on-surface sm:px-6 lg:px-12">{catatan}</p>
                </div>
            )}
        </footer>
    );
}
