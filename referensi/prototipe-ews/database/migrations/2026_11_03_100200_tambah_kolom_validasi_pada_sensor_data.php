<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->boolean('kabel_putus')->default(false);
            $table->string('validasi')->nullable();          // menunggu / disetujui / ditolak
            $table->timestamp('divalidasi_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sensor_data', function (Blueprint $table) {
            $table->dropColumn(['kabel_putus', 'validasi', 'divalidasi_pada']);
        });
    }
};
