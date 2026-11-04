<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->decimal('kemiringan', 6, 2)->nullable();        // derajat
            $table->decimal('curah_hujan', 7, 2)->nullable();       // mm per jam
            $table->decimal('kelembapan_udara', 5, 2)->nullable();  // persen
            $table->string('pemicu')->nullable();                   // alasan status, mis. "Curah hujan 24 mm/jam"
        });

        // Kolom kelembapan (tanah) boleh kosong: kalau satu sensor rusak, data sensor lain tetap tersimpan.
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->integer('kelembapan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->dropColumn(['kemiringan', 'curah_hujan', 'kelembapan_udara', 'pemicu']);
        });
    }
};
