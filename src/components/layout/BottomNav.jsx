import { cn } from '../../lib/cn.js';
import { Icon } from '../ui/index.js';

/** Navigasi bawah untuk HP (disembunyikan mulai layar `md`). Props: item [{ label, ikon, href, aktif? }] */
export default function BottomNav({ item }) {
    return (
        <nav
            aria-label="Navigasi bawah"
            className="fixed bottom-0 left-0 z-50 flex w-full items-center justify-around border-t border-outline-variant/30 bg-surface-container-lowest px-1 py-1 shadow-lg md:hidden"
        >
            {item.map((t) => (
                <a
                    key={t.href}
                    href={t.href}
                    aria-current={t.aktif ? 'page' : undefined}
                    className={cn(
                        'flex min-h-12 min-w-16 flex-col items-center justify-center rounded-lg px-3 py-1 text-label-sm font-label-sm transition-colors',
                        t.aktif ? 'bg-secondary-container text-on-secondary-container' : 'text-on-surface-variant hover:bg-surface-container',
                    )}
                >
                    <Icon name={t.ikon} size={22} filled={t.aktif} />
                    {t.label}
                </a>
            ))}
        </nav>
    );
}
