<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Potensi desa: daerah apa, penghasil apa, di mana, dan fotonya.
        Schema::create('profil_potensi', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 120);
            $table->string('kategori', 20)->index();
            $table->text('hasil')->nullable();
            $table->string('lokasi', 255)->nullable();
            $table->string('luas', 40)->nullable();
            $table->text('keterangan')->nullable();
            // foto = unggahan admin (disk public); foto_bawaan = berkas di folder public/ yang ikut Git.
            $table->string('foto')->nullable();
            $table->string('foto_bawaan')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_potensi');
    }
};
