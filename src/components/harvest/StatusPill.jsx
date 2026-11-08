/** Penanda status kecil dengan titik berdenyut (mis. "Transparansi Terbuka untuk Warga"). Titik berhenti bila gerak dikurangi. */
export default function StatusPill({ children }) {
    return (
        <div className="flex items-center gap-2 whitespace-nowrap rounded-full border border-outline-variant/40 bg-surface-container px-3 py-1.5 text-label-sm font-label-sm text-on-surface-variant">
            <span aria-hidden="true" className="h-2 w-2 animate-pulse rounded-full bg-secondary motion-reduce:animate-none" />
            <span>{children}</span>
        </div>
    );
}
