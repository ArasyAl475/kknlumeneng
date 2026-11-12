<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Titik yang tidak mengirim data sensor: EWS alarm tanpa integrasi dan titik rawan longsor.
        // Sengaja terpisah dari ews_wilayah supaya status, alat offline, dan API alat tidak ikut terpengaruh.
        Schema::create('ews_titik', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->string('jenis', 20)->index();
            $table->decimal('lintang', 9, 6);
            $table->decimal('bujur', 9, 6);
            $table->json('poligon')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ews_titik');
    }
};
