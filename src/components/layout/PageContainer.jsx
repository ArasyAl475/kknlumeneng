import { cn } from '../../lib/cn.js';

/**
 * Kerangka isi halaman: lebar maksimal 7xl di tengah, padding 1rem (HP) sampai 3rem (layar lebar),
 * dan jarak antarbagian 2rem. Beri `pb-28` bila memakai BottomNav agar isi tidak tertutup.
 */
export default function PageContainer({ className, children }) {
    return <main id="isi" className={cn('mx-auto w-full max-w-7xl flex-1 space-y-8 px-4 py-8 sm:px-6 lg:px-12', className)}>{children}</main>;
}
