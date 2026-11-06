import { cn } from '../../lib/cn.js';
import { Badge, Icon } from '../ui/index.js';

/**
 * Bilah atas hijau tua: identitas desa, menu (layar lebar), dan tautan Peringatan Dini.
 * Props:
 *   brand   { nama, wilayah, ikon, lencana? }
 *   menu    [{ label, href, ikon, aktif? }]
 *   peringatan { label, href }   tautan peringatan dini
 *   aksi    { label, href, ikon } tombol sekunder (opsional)
 */
export default function TopAppBar({ brand, menu = [], peringatan, aksi }) {
    return (
        <header className="sticky top-0 z-50 w-full bg-primary shadow-sm">
            <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-12">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg border border-surface-container-lowest/20 bg-surface-container-lowest/10 text-on-primary">
                        <Icon name={brand.ikon ?? 'nature_people'} size={24} />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="text-headline-sm font-headline-sm tracking-tight text-on-primary">{brand.nama}</span>
                            {brand.lencana && (
                                <Badge tone="mint" pill className="hidden sm:inline-flex">
                                    {brand.lencana}
                                </Badge>
                            )}
                        </div>
                        <p className="text-body-sm font-body-sm leading-none text-on-primary-container">{brand.wilayah}</p>
                    </div>
                </div>

                {/* Menu hanya digambar bila ada isinya (rancangan terbaru mengosongkannya). */}
                {menu.length > 0 && (
                <nav aria-label="Menu utama" className="hidden h-full items-center gap-6 pt-1 md:flex">
                    {menu.map((item) => (
                        <a
                            key={item.href}
                            href={item.href}
                            aria-current={item.aktif ? 'page' : undefined}
                            className={cn(
                                'flex items-center gap-1.5 pb-1 text-label-md font-label-md transition-colors',
                                item.aktif
                                    ? 'border-b-2 border-secondary-container font-semibold text-on-primary'
                                    : 'text-surface-variant hover:text-on-primary',
                            )}
                        >
                            {item.ikon && <Icon name={item.ikon} size={18} />}
                            {item.label}
                        </a>
                    ))}
                </nav>
                )}

                <div className="flex items-center gap-3">
                    {peringatan && (
                        <a
                            href={peringatan.href}
                            className="flex min-h-10 items-center gap-1.5 rounded-lg border border-error/20 bg-error-container px-3 py-1.5 text-label-md font-label-md font-semibold text-on-error-container transition-all duration-150 hover:bg-error/20 active:scale-95"
                        >
                            <Icon name="warning" size={18} className="text-error" />
                            {peringatan.label}
                        </a>
                    )}
                    {aksi && (
                        <a
                            href={aksi.href}
                            className="hidden items-center gap-1.5 rounded-lg border border-surface-container-lowest/20 bg-surface-container-lowest/10 px-3 py-1.5 text-label-md font-label-md text-on-primary transition-colors hover:bg-surface-container-lowest/20 sm:inline-flex"
                        >
                            {aksi.ikon && <Icon name={aksi.ikon} size={18} />}
                            {aksi.label}
                        </a>
                    )}
                </div>
            </div>
        </header>
    );
}
