// Mengambil /api/ews/data berkala. Hanya memanggil `saatData` bila isinya benar-benar berubah (ETag),
// jadi di sinyal lemah balasannya 304 tanpa isi.
const JEDA_NORMAL = 10_000;
const JEDA_MAKS = 60_000;

/**
 * @param url               alamat data dasbor (route ews.data)
 * @param pilihan.saatData  (data) => void, dipanggil saat ada data baru
 * @param pilihan.saatKoneksi ({ gagal, waktu }) => void, dipanggil tiap selesai mencoba
 */
export function mulaiPembaruan(url, { saatData, saatKoneksi = () => {} }) {
    let etagTerakhir = null;
    let gagalBeruntun = 0;
    let waktuTerakhir = new Date();

    async function ambilData() {
        if (document.hidden) return; // Tab tak terlihat tidak perlu menghabiskan kuota.

        try {
            const respons = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: AbortSignal.timeout(8000),
            });
            if (!respons.ok) throw new Error(`HTTP ${respons.status}`);

            const etag = respons.headers.get('ETag');
            if (etag === null || etag !== etagTerakhir) {
                const data = await respons.json();
                etagTerakhir = etag;
                saatData(data);
            }
            gagalBeruntun = 0;
            waktuTerakhir = new Date();
        } catch {
            gagalBeruntun += 1;
        }
        saatKoneksi({ gagal: gagalBeruntun, waktu: waktuTerakhir });
    }

    function jadwalkan() {
        // Saat sinyal hilang, jeda dibuat makin panjang agar tidak terus-menerus membuang baterai.
        const jeda = Math.min(JEDA_MAKS, JEDA_NORMAL * 2 ** gagalBeruntun);
        setTimeout(async () => {
            await ambilData();
            jadwalkan();
        }, jeda);
    }

    saatKoneksi({ gagal: 0, waktu: waktuTerakhir });
    jadwalkan();

    // Begitu sinyal kembali atau tab dibuka lagi, data langsung disegarkan tanpa menunggu jadwal.
    window.addEventListener('online', ambilData);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) ambilData();
    });
}
