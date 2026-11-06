/**
 * Baris kartu angka. Anak pertama biasanya <HeroMetricCard /> (lebar penuh di tablet, satu kolom di desktop).
 * HP: 1 kolom. Tablet: 2 kolom. Desktop: 5 kolom (1 sorotan + 4 angka).
 */
export default function MetricGrid({ children }) {
    return (
        <section aria-label="Ringkasan angka" className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            {children}
        </section>
    );
}
