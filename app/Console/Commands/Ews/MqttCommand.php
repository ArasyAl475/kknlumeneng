<?php

namespace App\Console\Commands\Ews;

use App\Services\Ews\PenerimaMqtt;
use App\Services\Ews\PenerimaPembacaan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Throwable;

class MqttCommand extends Command
{
    protected $signature = 'ews:mqtt {--detik=0 : Berhenti sendiri setelah sekian detik (0 = terus berjalan)}';

    protected $description = 'Berlangganan broker MQTT dan menyimpan kiriman alat EWS (panduan: docs/ews/PANDUAN_MQTT.md)';

    private float $terakhirMemproses = 0;

    public function handle(): int
    {
        $cfg = config('ews.mqtt');

        if (! $cfg['aktif']) {
            $this->warn('MQTT belum diaktifkan (EWS_MQTT_AKTIF=false). Tidak ada yang dijalankan.');

            return self::SUCCESS;
        }

        if (blank($cfg['host'])) {
            $this->error('EWS_MQTT_HOST di .env masih kosong.');

            return self::FAILURE;
        }

        // Kata sandi broker terkirim saat menyambung; tanpa TLS ia terbaca siapa saja di jalur jaringan.
        if (! $cfg['tls'] && $this->laravel->isProduction()) {
            $this->error('Di produksi MQTT wajib memakai TLS (EWS_MQTT_TLS=true).');

            return self::FAILURE;
        }

        $penerima = new PenerimaMqtt(new PenerimaPembacaan);

        $klien = new MqttClient($cfg['host'], $cfg['port'], $cfg['client_id'], MqttClient::MQTT_3_1_1);

        $pengaturan = (new ConnectionSettings)
            ->setUsername(filled($cfg['username']) ? $cfg['username'] : null)
            ->setPassword(filled($cfg['password']) ? $cfg['password'] : null)
            ->setUseTls((bool) $cfg['tls'])
            ->setTlsVerifyPeer(true)
            ->setTlsVerifyPeerName(true)
            ->setTlsSelfSignedAllowed(false)
            ->setTlsCertificateAuthorityFile(filled($cfg['ca_file']) ? $cfg['ca_file'] : null)
            ->setConnectTimeout(10)
            ->setKeepAliveInterval(30)
            ->setReconnectAutomatically(true)
            ->setMaxReconnectAttempts(10)
            ->setDelayBetweenReconnectAttempts(3000);

        try {
            // Sesi tidak dibersihkan: pesan QoS 1 yang tiba saat web sedang tidak berlangganan
            // disimpan broker dan dikirim begitu perintah ini tersambung lagi.
            $klien->connect($pengaturan, false);
        } catch (Throwable $galat) {
            $this->error('Gagal tersambung ke broker: '.$galat->getMessage());

            return self::FAILURE;
        }

        $this->info("Tersambung ke {$cfg['host']}:{$cfg['port']}, menunggu data di {$penerima->polaTopikLangganan()}");

        $klien->subscribe($penerima->polaTopikLangganan(), function (string $topik, string $isi, bool $retained) use ($klien, $penerima): void {
            $hasil = $this->olah($penerima, $topik, $isi, $retained);

            $this->line(sprintf(
                '[%s] %s -> %s',
                now()->format('d-m-Y H:i:s'),
                $topik,
                $hasil['diterima'] ? "tersimpan ({$hasil['status']})" : "DITOLAK: {$hasil['alasan']}",
            ));

            $this->balas($klien, $penerima, $hasil);
        }, MqttClient::QOS_AT_LEAST_ONCE);

        $batas = (int) $this->option('detik');
        if ($batas > 0) {
            $klien->registerLoopEventHandler(function (MqttClient $k, float $berjalan) use ($batas): void {
                if ($berjalan >= $batas) {
                    $k->interrupt();
                }
            });
        }

        // Di Linux (supervisor/systemd) berhenti dengan rapi saat diminta; di Windows tidak tersedia.
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            foreach ([SIGINT, SIGTERM] as $sinyal) {
                pcntl_signal($sinyal, fn () => $klien->interrupt());
            }
        }

        $kode = self::SUCCESS;

        try {
            $klien->loop(true);
        } catch (Throwable $galat) {
            $this->error('Koneksi ke broker terputus: '.$galat->getMessage());
            $kode = self::FAILURE;
        } finally {
            try {
                $klien->disconnect();
            } catch (Throwable) {
                // Sudah terputus; tidak ada yang perlu dibersihkan.
            }
        }

        return $kode;
    }

    /**
     * @return array{diterima: bool, wilayah_id: ?int, status?: string, alasan?: string}
     */
    private function olah(PenerimaMqtt $penerima, string $topik, string $isi, bool $retained): array
    {
        try {
            // Server basis data menutup koneksi yang menganggur lama; buka ulang sebelum dipakai.
            if (microtime(true) - $this->terakhirMemproses > 30) {
                DB::reconnect();
            }
            $this->terakhirMemproses = microtime(true);

            return $penerima->proses($topik, $isi, $retained);
        } catch (Throwable $galat) {
            Log::error('EWS MQTT gagal memproses pesan: '.$galat->getMessage());

            return ['diterima' => false, 'wilayah_id' => null, 'alasan' => 'Galat server saat menyimpan'];
        }
    }

    /**
     * Balasan membantu pembuat alat mencari tahu kenapa data ditolak (MQTT tidak punya kode 422).
     * Gagal membalas tidak boleh menghentikan penerimaan data.
     *
     * @param  array{diterima: bool, wilayah_id: ?int, status?: string, alasan?: string}  $hasil
     */
    private function balas(MqttClient $klien, PenerimaMqtt $penerima, array $hasil): void
    {
        if ($hasil['wilayah_id'] === null) {
            return;
        }

        try {
            $klien->publish(
                $penerima->topikBalasan($hasil['wilayah_id']),
                json_encode(['diterima' => $hasil['diterima'], 'status' => $hasil['status'] ?? null, 'alasan' => $hasil['alasan'] ?? null], JSON_UNESCAPED_UNICODE),
                MqttClient::QOS_AT_MOST_ONCE,
            );
        } catch (Throwable $galat) {
            Log::warning('EWS MQTT gagal mengirim balasan: '.$galat->getMessage());
        }
    }
}
