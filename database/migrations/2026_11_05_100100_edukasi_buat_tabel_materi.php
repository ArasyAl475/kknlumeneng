<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edukasi_materi', function (Blueprint $table) {
            $table->id();
            // Kategori yang masih berisi materi tidak boleh hilang, agar materi tidak ikut terhapus.
            $table->foreignId('kategori_id')->constrained('edukasi_kategori')->restrictOnDelete();
            $table->string('judul', 150);
            $table->string('slug', 170)->unique();
            $table->string('ringkasan', 300);
            $table->text('penjelasan')->nullable();
            $table->text('manfaat')->nullable();
            $table->text('alat_bahan')->nullable();
            $table->text('ciri_berhasil')->nullable();
            $table->text('ciri_gagal')->nullable();
            $table->text('catatan_keamanan')->nullable();
            $table->string('gambar')->nullable();
            // Kosong berarti draf. Satu kolom ini cukup, sehingga status dan tanggal tidak bisa saling bertentangan.
            $table->timestamp('diterbitkan_pada')->nullable();
            $table->timestamps();

            $table->index(['kategori_id', 'diterbitkan_pada']);
            $table->index('diterbitkan_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edukasi_materi');
    }
};
