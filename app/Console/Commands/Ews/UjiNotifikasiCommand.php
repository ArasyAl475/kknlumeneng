<?php

namespace App\Console\Commands\Ews;

use App\Models\Ews\Wilayah;
use App\Services\Ews\NotifikasiWarga;
use App\Services\Ews\PushFcm;
use Illuminate\Console\Command;
use Throwable;

class UjiNotifikasiCommand extends Command
{
    protected $signature = 'ews:uji-notifikasi
        {status=waspada : awas | waspada | offline}
        {--wilayah= : ID wilayah (seperti di /api/ews/data); bawaan wilayah pertama}
        {--paksa : kirim tanpa pertanyaan konfirmasi}
        {--periksa-saja : Firebase hanya memeriksa kunci dan isi pesan, TIDAK ada HP yang menerima}';

    protected $description = 'Kirim notifikasi UJI ke semua HP yang memasang aplikasi EWS Lumeneng';

    public function handle(PushFcm $fcm, NotifikasiWarga $notifikasi): int
    {
        $status = (string) $this->argument('status');
        if (! in_array($status, [NotifikasiWarga::AWAS, NotifikasiWarga::WASPADA, NotifikasiWarga::OFFLINE], true)) {
            $this->error('Status harus awas, waspada, atau offline.');

            return self::INVALID;
        }

        if (($masalah = $fcm->masalah()) !== null) {
            $this->error('Notifikasi belum siap: '.$masalah);

            return self::FAILURE;
        }

        $wilayah = $this->option('wilayah')
            ? Wilayah::query()->find((int) $this->option('wilayah'))
            : Wilayah::query()->orderBy('id')->first();

        if ($wilayah === null) {
            $this->error('Wilayah tidak ditemukan. Lihat ID di Admin -> Daftar wilayah atau di /api/ews/data.');

            return self::FAILURE;
        }

        $periksaSaja = (bool) $this->option('periksa-saja');

        // Pesan uji sampai ke SEMUA HP warga, jadi selalu ditandai [UJI] dan ditanyakan dulu.
        if (! $periksaSaja && ! $this->option('paksa')
            && ! $this->confirm("Notifikasi UJI \"{$status}\" untuk {$wilayah->nama} akan sampai ke SEMUA HP warga. Lanjutkan?")) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        [$judul] = $notifikasi->teks($status, $wilayah->nama, null);
        $pesan = $notifikasi->pesan($wilayah->id, $status, '[UJI] '.$judul, 'Ini hanya uji coba sistem, bukan peringatan sungguhan.');

        try {
            $nama = $fcm->kirim($pesan, $periksaSaja);
        } catch (Throwable $e) {
            $this->error('Gagal: '.mb_substr($e->getMessage(), 0, 500));

            return self::FAILURE;
        }

        if ($periksaSaja) {
            $this->info('Kunci Firebase dan isi pesan BENAR. Tidak ada HP yang menerima (mode periksa saja).');
            $this->line('Untuk mengirim sungguhan, jalankan lagi tanpa --periksa-saja.');

            return self::SUCCESS;
        }

        $this->info('Terkirim ke topik "'.config('ews.push.topik')."\" ({$nama}).");
        $this->line('Notifikasi seharusnya muncul di HP dalam beberapa detik, juga saat aplikasi tertutup.');

        return self::SUCCESS;
    }
}
