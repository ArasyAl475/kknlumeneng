<?php

namespace App\Services\Ews;

use App\Models\Ews\Wilayah;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mengabarkan perubahan status wilayah ke HP warga (FCM, topik warga-lumeneng).
 *
 * Status yang dinilai sama persis dengan yang dilihat warga di dashboard (DataDasbor), jadi notifikasi
 * tidak pernah berbeda dengan halaman /ews. Aturan kirim: docs/ews/NOTIFIKASI_HP.md.
 */
class NotifikasiWarga
{
    public const AWAS = 'awas';

    // Waspada dari sebagian sensor: tetap dikabarkan, tapi lewat saluran senyap.
    public const WASPADA = 'waspada';

    // Waspada yang berbunyi: SEMUA sensor berambang sudah level 1/2 dan alat masih mengirim.
    public const WASPADA_SEMUA = 'waspada_semua';

    public const OFFLINE = 'offline';

    public const AMAN = 'aman';

    public const KOSONG = 'kosong';

    public function __construct(private PushFcm $fcm, private DataDasbor $dasbor) {}

    /**
     * Dipanggil setiap data dashboard berubah (DataDasbor::lupakan). Di permintaan web, pemeriksaan
     * dijalankan SETELAH balasan terkirim, sehingga alat tidak ikut menunggu Firebase.
     */
    public static function periksaNanti(): void
    {
        if (! config('ews.push.aktif')) {
            return;
        }

        // Perintah konsol (MQTT, penjadwal, tes) tidak punya balasan yang perlu didahulukan.
        if (app()->runningInConsole()) {
            app(self::class)->periksa();

            return;
        }

        dispatch(fn () => app(self::class)->periksa())->afterResponse();
    }

    /**
     * @return int jumlah notifikasi yang terkirim
     */
    public function periksa(): int
    {
        if (! $this->fcm->siap()) {
            return 0;
        }

        $terkirim = 0;

        try {
            foreach ($this->dasbor->ambil()['wilayah'] as $wilayah) {
                $terkirim += $this->periksaWilayah($wilayah) ? 1 : 0;
            }
        } catch (Throwable $e) {
            // Notifikasi tidak boleh menggagalkan penyimpanan data sensor atau keputusan admin.
            report($e);
        }

        return $terkirim;
    }

    /**
     * @param  array{id: int, nama: string, status: ?string, pemicu: ?string, offline: bool, sensor: array<string, array{level: ?int}>}  $wilayah
     */
    private function periksaWilayah(array $wilayah): bool
    {
        // Level diambil dari data dashboard, sama dengan `level` di /api/ews/data, bukan dihitung ulang.
        $level = array_map(fn (string $s) => $wilayah['sensor'][$s]['level'] ?? null, PenilaianStatus::SENSOR_DINILAI);
        $baru = self::kode($wilayah['status'], $wilayah['offline'], $level);
        $lama = Wilayah::query()->whereKey($wilayah['id'])->value('status_notifikasi');

        // Sensor yang naik-turun di sekitar ambang tidak boleh membunyikan HP berkali-kali: selama masih Waspada,
        // turun dari "semua sensor" ke "sebagian" tidak dikirim dan tidak dicatat.
        if ($lama === self::WASPADA_SEMUA && $baru === self::WASPADA) {
            return false;
        }

        if ($lama === $baru || ! $this->catat($wilayah['id'], $lama, $baru)) {
            return false;
        }

        // Pertama kali dicatat (kolom baru dipasang / wilayah baru): status yang sudah berlangsung tidak
        // dikabarkan, agar memasang fitur ini tidak membuat semua HP berbunyi.
        if ($lama === null || ! $this->perluDikirim($wilayah['id'], $baru)) {
            return false;
        }

        [$judul, $isi] = $this->teks($baru, $wilayah['nama'], $wilayah['pemicu']);

        try {
            $this->fcm->kirim($this->pesan($wilayah['id'], $baru, $judul, $isi));
            Log::info('EWS push terkirim', ['wilayah' => $wilayah['id'], 'status' => $baru]);

            return true;
        } catch (Throwable $e) {
            // Dikembalikan ke status lama supaya dicoba lagi pada pemeriksaan berikutnya
            // (paling lambat penjadwal tiap menit): Awas yang gagal terkirim tidak boleh hilang.
            $this->catat($wilayah['id'], $baru, $lama);
            Cache::forget($this->kunciJeda($wilayah['id'], $baru));
            Log::warning('EWS push gagal', ['wilayah' => $wilayah['id'], 'status' => $baru, 'galat' => mb_substr($e->getMessage(), 0, 300)]);

            return false;
        }
    }

    /**
     * @param  array<int, ?int>  $levelBerambang  level sensor yang punya ambang; null = sensor tidak mengirim nilai
     */
    public static function kode(?string $status, bool $offline, array $levelBerambang = []): string
    {
        // Sensor tanpa nilai dianggap belum Waspada, jadi tidak ikut membuat HP berbunyi.
        $semuaWaspada = $levelBerambang !== []
            && collect($levelBerambang)->every(fn (?int $level) => $level !== null && $level >= 1);

        return match (true) {
            $status === PenilaianStatus::AWAS => self::AWAS,
            $status === null => self::KOSONG,
            $offline => self::OFFLINE,
            $status === PenilaianStatus::WASPADA && $semuaWaspada => self::WASPADA_SEMUA,
            $status === PenilaianStatus::WASPADA => self::WASPADA,
            default => self::AMAN,
        };
    }

    /**
     * @return array<string, mixed> isi "message" FCM v1, sesuai kontrak aplikasi EWS Lumeneng
     */
    public function pesan(int $wilayahId, string $kode, string $judul, string $isi): array
    {
        // Aplikasi menentukan bunyi dari channel_id. Nama channel harus persis: channel yang salah atau
        // kosong membuat Android memakai channel Waspada, yang berbunyi.
        $channel = match ($kode) {
            self::AWAS => 'ews_awas_v3',
            self::WASPADA_SEMUA => 'ews_waspada_v3',
            self::WASPADA => 'ews_waspada_senyap_v1',
            default => 'ews_offline_v1',
        };

        // "sound" hanya dipakai Android 7 ke bawah; kiriman senyap tidak boleh membawanya.
        $suara = match ($kode) {
            self::AWAS => 'ews_awas',
            self::WASPADA_SEMUA => 'ews_waspada',
            default => null,
        };

        return [
            'topic' => (string) config('ews.push.topik'),
            // Tanpa "notification", Android tidak menampilkan apa pun saat aplikasi tertutup.
            'notification' => ['title' => $judul, 'body' => $isi],
            // Semua nilai data wajib teks. Aplikasi hanya mengenal awas/waspada/offline.
            'data' => ['status' => $kode === self::WASPADA_SEMUA ? self::WASPADA : $kode, 'wilayah_id' => (string) $wilayahId],
            'android' => [
                // high: tetap diantar saat layar mati (mode Doze).
                'priority' => 'high',
                // HP yang mati lebih dari 6 jam tidak menerima peringatan yang sudah basi.
                'ttl' => '21600s',
                'notification' => array_filter([
                    'channel_id' => $channel,
                    'sound' => $suara,
                    // Peringatan baru untuk wilayah yang sama menggantikan yang lama, tidak menumpuk.
                    'tag' => "ews-wilayah-{$wilayahId}",
                    'notification_priority' => 'PRIORITY_MAX',
                    'visibility' => 'PUBLIC',
                ]),
            ],
        ];
    }

    /**
     * @return array{0: string, 1: string} judul dan isi
     */
    public function teks(string $kode, string $nama, ?string $pemicu): array
    {
        // Pesan bisa terlambat sampai, jadi jam kejadian selalu dicantumkan.
        $jam = now()->format('H.i').' WIB';
        $sebab = rtrim(trim((string) $pemicu), '.');
        $sebab = $sebab !== '' ? $sebab.'. ' : '';

        return match ($kode) {
            self::AWAS => ["Awas: {$nama}", "{$sebab}Segera menjauh dari lereng dan ikuti arahan petugas. ({$jam})"],
            self::WASPADA_SEMUA => ["Waspada: {$nama}", "Semua sensor menyentuh batas Waspada. {$sebab}Siapkan diri dan pantau kabar dari petugas. ({$jam})"],
            self::WASPADA => ["Waspada: {$nama}", "{$sebab}Pantau kabar dari petugas. ({$jam})"],
            default => ["{$nama} tidak mengirim data", "Kondisi sekarang belum diketahui, jangan anggap sudah aman. ({$jam})"],
        };
    }

    // Pembaruan bersyarat: bila dua kiriman alat diperiksa bersamaan, hanya satu yang mengirim notifikasi.
    private function catat(int $id, ?string $dari, ?string $ke): bool
    {
        return Wilayah::query()->whereKey($id)
            ->where(fn ($q) => $dari === null ? $q->whereNull('status_notifikasi') : $q->where('status_notifikasi', $dari))
            ->toBase()
            ->update(['status_notifikasi' => $ke]) === 1;
    }

    private function perluDikirim(int $id, string $kode): bool
    {
        return match ($kode) {
            self::AWAS => true,
            // Cache::add atomik: status yang bolak-balik Aman <-> Waspada tidak membuat HP berbunyi terus.
            self::WASPADA, self::WASPADA_SEMUA => Cache::add($this->kunciJeda($id, $kode), true, now()->addMinutes(max(1, (int) config('ews.push.jeda_menit')))),
            self::OFFLINE => (bool) config('ews.push.kirim_offline')
                && Cache::add($this->kunciJeda($id, $kode), true, now()->addMinutes(max(1, (int) config('ews.push.jeda_menit')))),
            // Aman tidak dikirim: aplikasi sudah mencatat kembali Normal sendiri saat dibuka.
            default => false,
        };
    }

    private function kunciJeda(int $id, string $kode): string
    {
        return "ews:push:jeda:{$id}:{$kode}";
    }
}
