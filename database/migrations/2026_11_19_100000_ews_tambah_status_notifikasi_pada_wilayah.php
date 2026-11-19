<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Status terakhir yang sudah dikabarkan ke HP warga (awas/waspada/offline/aman/kosong).
    // Disimpan di basis data, bukan cache, agar `cache:clear` tidak membuat semua HP berbunyi lagi.
    // null = belum pernah dicatat: pemeriksaan pertama hanya mencatat, tidak mengirim.
    public function up(): void
    {
        Schema::table('ews_wilayah', function (Blueprint $table) {
            $table->string('status_notifikasi', 10)->nullable()->after('awas_sejak');
        });
    }

    public function down(): void
    {
        Schema::table('ews_wilayah', function (Blueprint $table) {
            $table->dropColumn('status_notifikasi');
        });
    }
};
