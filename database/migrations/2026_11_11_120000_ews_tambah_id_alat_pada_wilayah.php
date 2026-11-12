<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ews_wilayah', function (Blueprint $table) {
            // ID yang dipasang di alat (WILAYAH_ID di ESP32, angka di topik MQTT). Dipisah dari id baris basis data
            // agar admin bisa mengganti angkanya tanpa menyentuh riwayat data. Unik; wilayah terhapus melepas angkanya.
            $table->unsignedInteger('id_alat')->nullable()->unique()->after('id');
        });

        // Perilaku lama: alat memakai id baris. Wilayah aktif mempertahankan angkanya, jadi alat yang sudah terpasang tetap diterima.
        DB::table('ews_wilayah')->whereNull('deleted_at')->update(['id_alat' => DB::raw('id')]);
    }

    public function down(): void
    {
        Schema::table('ews_wilayah', function (Blueprint $table) {
            $table->dropUnique(['id_alat']);
            $table->dropColumn('id_alat');
        });
    }
};
