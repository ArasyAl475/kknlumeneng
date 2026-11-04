<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_data', function (Blueprint $table) {
            $table->id();
            $table->string('lokasi')->index();
            $table->integer('kelembapan');   // persen, 0-100
            $table->string('status');        // Aman / Waspada / Bahaya
            $table->string('warna');         // hijau / kuning / merah
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_data');
    }
};
