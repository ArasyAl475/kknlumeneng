<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu tabel untuk semua foto galeri. berita_id terisi = dokumentasi kegiatan (satu berita
        // punya banyak foto); kosong = foto desa mandiri yang tidak terhubung ke mana pun.
        Schema::create('profil_galeri', function (Blueprint $table) {
            $table->id();
            // Foto kegiatan ikut terhapus bersama beritanya (berkasnya dihapus oleh PengelolaBerita).
            $table->foreignId('berita_id')->nullable()->constrained('profil_berita')->cascadeOnDelete();
            $table->string('gambar');
            $table->string('judul', 150)->nullable();
            $table->string('keterangan', 500)->nullable();
            // Tanggal foto diambil; bila kosong, dipakai tanggal terbit beritanya atau tanggal unggah.
            $table->date('tanggal')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['berita_id', 'urutan']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_galeri');
    }
};
