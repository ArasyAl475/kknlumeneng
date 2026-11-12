<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sekarang = now();
        $ids = DB::table('ews_wilayah')->whereNull('deleted_at')->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($ids, $sekarang) {
            // Sama dengan tombol Hapus di admin: laporan yang menunggu tidak bisa diputuskan lagi
            // setelah wilayahnya hilang, jadi ditutup sebagai ditolak. Riwayat datanya tetap ada.
            DB::table('ews_pembacaan')->whereIn('wilayah_id', $ids)->where('validasi', 'menunggu')
                ->update(['validasi' => 'ditolak', 'divalidasi_pada' => $sekarang]);

            DB::table('ews_wilayah')->whereIn('id', $ids)
                ->update(['status_awas' => false, 'awas_sumber' => null, 'awas_sejak' => null, 'deleted_at' => $sekarang]);
        });
    }

    // Dipulihkan lewat tombol Pulihkan di Daftar wilayah; tidak ada yang perlu dibalik di sini.
    public function down(): void {}
};
