<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edukasi_materi', function (Blueprint $table) {
            // Yang disimpan hanya kode video (11 karakter), bukan tautan penuh, agar alamat pemutar
            // selalu dibentuk oleh aplikasi sendiri dan tidak bisa diisi alamat sembarang.
            $table->string('video_youtube', 11)->nullable()->after('gambar');
            $table->dropColumn(['manfaat', 'alat_bahan', 'ciri_berhasil', 'ciri_gagal', 'catatan_keamanan']);
        });
    }

    public function down(): void
    {
        Schema::table('edukasi_materi', function (Blueprint $table) {
            $table->dropColumn('video_youtube');
            $table->text('manfaat')->nullable();
            $table->text('alat_bahan')->nullable();
            $table->text('ciri_berhasil')->nullable();
            $table->text('ciri_gagal')->nullable();
            $table->text('catatan_keamanan')->nullable();
        });
    }
};
