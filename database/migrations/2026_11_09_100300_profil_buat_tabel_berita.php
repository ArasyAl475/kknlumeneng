<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_berita', function (Blueprint $table) {
            $table->id();
            // berita, pengumuman, atau artikel. Daftar tetap agar admin tidak perlu mengelola kategori.
            $table->string('jenis', 20);
            $table->string('judul', 150);
            $table->string('slug', 170)->unique();
            $table->string('ringkasan', 300);
            // mediumText: isi berformat HTML (dengan foto di dalamnya) bisa melebihi batas 64 KB kolom text di MySQL.
            $table->mediumText('isi');
            $table->string('gambar')->nullable();
            $table->string('penulis', 100)->nullable();
            // Kosong berarti draf, sehingga status dan tanggal terbit tidak bisa saling bertentangan.
            $table->timestamp('diterbitkan_pada')->nullable();
            $table->timestamps();

            $table->index(['jenis', 'diterbitkan_pada']);
            $table->index('diterbitkan_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_berita');
    }
};
