<?php

namespace App\Services\Ews;

use App\Models\Ews\Wilayah;
use Illuminate\Support\Facades\Cache;

/**
 * Mengolah satu pesan MQTT dari alat menjadi pembacaan. Tanpa koneksi broker sama sekali,
 * jadi seluruh aturannya bisa diuji tanpa alat dan tanpa broker.
 */
class PenerimaMqtt
{
    // Isi kiriman sebenarnya ~150 byte; batas ini hanya menahan pesan sampah.
    private const BATAS_BYTE = 2048;

    public function __construct(private PenerimaPembacaan $penerima) {}

    public function polaTopikLangganan(): string
    {
        return $this->awalan().'/+/sensor';
    }

    public function topikBalasan(int $wilayahId): string
    {
        return $this->awalan()."/{$wilayahId}/balasan";
    }

    /**
     * @return array{diterima: bool, wilayah_id: ?int, status?: string, alasan?: string}
     */
    public function proses(string $topik, string $isi, bool $retained = false): array
    {
        $wilayahId = $this->wilayahDariTopik($topik);

        // Pesan "retained" adalah salinan lama yang disimpan broker. Menyimpannya sekarang akan
        // memberi jam server baru pada data lama dan terlihat seolah alat baru mengirim.
        if ($retained) {
            return $this->tolak($wilayahId, 'Pesan retained diabaikan; terbitkan dengan retain=false');
        }

        if ($wilayahId === null) {
            return $this->tolak(null, 'Topik tidak dikenal');
        }

        if (strlen($isi) > self::BATAS_BYTE) {
            return $this->tolak($wilayahId, 'Isi terlalu besar');
        }

        $data = json_decode($isi, true);
        if (! is_array($data) || array_is_list($data)) {
            return $this->tolak($wilayahId, 'Isi bukan JSON objek yang valid');
        }

        // Wilayah ditentukan topik (yang dijaga izin broker), bukan isi yang bisa salah ketik.
        if (isset($data['wilayah_id']) && (int) $data['wilayah_id'] !== $wilayahId) {
            return $this->tolak($wilayahId, 'wilayah_id di isi tidak sama dengan topik');
        }

        $validator = KirimanAlat::validator($data);
        if ($validator->fails()) {
            return $this->tolak($wilayahId, 'Data tidak valid: '.$validator->errors()->first());
        }

        $wilayah = Wilayah::query()->find($wilayahId);
        if ($wilayah === null) {
            return $this->tolak($wilayahId, 'Wilayah tidak dikenal');
        }

        if (! $this->lolosJeda($wilayahId)) {
            return $this->tolak($wilayahId, 'Terlalu sering; beri jarak minimal '.config('ews.mqtt.jeda_minimal_detik').' detik');
        }

        $tervalidasi = $validator->validated();
        $pembacaan = $this->penerima->terima(
            $wilayah,
            KirimanAlat::bacaan($tervalidasi),
            filter_var($tervalidasi['kabel_putus'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );

        return ['diterima' => true, 'wilayah_id' => $wilayahId, 'status' => $pembacaan->status];
    }

    private function awalan(): string
    {
        return trim((string) config('ews.mqtt.topik_awalan'), '/');
    }

    private function wilayahDariTopik(string $topik): ?int
    {
        $pola = '#^'.preg_quote($this->awalan(), '#').'/(\d{1,9})/sensor$#';

        return preg_match($pola, $topik, $cocok) === 1 ? (int) $cocok[1] : null;
    }

    private function lolosJeda(int $wilayahId): bool
    {
        $jeda = (int) config('ews.mqtt.jeda_minimal_detik');

        return $jeda <= 0 || Cache::add("ews:mqtt:jeda:{$wilayahId}", true, now()->addSeconds($jeda));
    }

    /**
     * @return array{diterima: false, wilayah_id: ?int, alasan: string}
     */
    private function tolak(?int $wilayahId, string $alasan): array
    {
        return ['diterima' => false, 'wilayah_id' => $wilayahId, 'alasan' => $alasan];
    }
}
