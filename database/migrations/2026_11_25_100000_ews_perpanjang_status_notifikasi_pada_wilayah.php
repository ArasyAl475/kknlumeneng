<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tingkat "waspada_semua" (13 huruf) tidak muat di kolom 10 huruf. Isi lama tetap berlaku apa adanya.
    public function up(): void
    {
        Schema::table('ews_wilayah', function (Blueprint $table) {
            $table->string('status_notifikasi', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('ews_wilayah')->where('status_notifikasi', 'waspada_semua')->update(['status_notifikasi' => 'waspada']);

        Schema::table('ews_wilayah', function (Blueprint $table) {
            $table->string('status_notifikasi', 10)->nullable()->change();
        });
    }
};
