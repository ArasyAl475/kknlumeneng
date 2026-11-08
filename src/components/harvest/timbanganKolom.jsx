import { formatKg } from '../../lib/format.js';
import { Badge } from '../ui/index.js';

// Titik warna jenis kopi: selain warna, nama jenis selalu tertulis di sebelahnya.
const TITIK = { robusta: 'bg-secondary', arabika: 'bg-tertiary' };

/**
 * Definisi kolom tabel "Transparansi timbangan hasil bumi warga" (untuk <HarvestTable kolom={...} />).
 * Baris: { id, tanggal, petani, blok, jenisKunci, jenis, kotor, potongan, bersih, status, statusIkon }
 */
export const kolomTimbangan = [
    { kunci: 'tanggal', label: 'Tanggal Timbang' },
    { kunci: 'petani', label: 'Nama Petani / Kelompok', render: (b) => <span className="font-semibold text-primary">{b.petani}</span> },
    { kunci: 'blok', label: 'Blok Lahan' },
    {
        kunci: 'jenis',
        label: 'Jenis Kopi',
        render: (b) => (
            <span className="inline-flex items-center gap-1 rounded bg-surface-container px-2 py-0.5 text-label-sm font-label-sm text-on-surface-variant">
                <span aria-hidden="true" className={`h-1.5 w-1.5 rounded-full ${TITIK[b.jenisKunci] ?? 'bg-outline'}`} />
                {b.jenis}
            </span>
        ),
    },
    { kunci: 'kotor', label: 'Berat Kotor', rata: 'kanan', render: (b) => <span className="text-outline">{formatKg(b.kotor)}</span> },
    { kunci: 'potongan', label: 'Potongan Air', rata: 'kanan', render: (b) => <span className="font-medium text-error">- {formatKg(b.potongan)}</span> },
    { kunci: 'bersih', label: 'Berat Bersih (kg)', rata: 'kanan', render: (b) => <strong>{formatKg(b.bersih)}</strong> },
    {
        kunci: 'status',
        label: 'Status Validasi',
        rata: 'tengah',
        render: (b) => (
            <Badge tone="mint" pill icon={b.statusIkon} className="px-2.5 font-medium">
                {b.status}
            </Badge>
        ),
    },
];
