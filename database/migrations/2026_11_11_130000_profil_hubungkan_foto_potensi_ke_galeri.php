<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Foto potensi disimpan di galeri (profil_galeri) supaya satu potensi bisa punya banyak foto
        // dan otomatis tampil di halaman Galeri. potensi_id terisi = foto milik potensi itu.
        Schema::table('profil_galeri', function (Blueprint $table) {
            $table->foreignId('potensi_id')->nullable()->after('berita_id')->constrained('profil_potensi')->cascadeOnDelete();
            $table->index(['potensi_id', 'urutan']);
        });

        // Foto tunggal lama yang sudah diunggah admin dipindahkan jadi satu baris galeri (berkasnya tidak berpindah).
        foreach (DB::table('profil_potensi')->whereNotNull('foto')->get(['id', 'nama', 'foto']) as $potensi) {
            DB::table('profil_galeri')->insert([
                'potensi_id' => $potensi->id,
                'gambar' => $potensi->foto,
                'judul' => $potensi->nama,
                'urutan' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('profil_potensi', function (Blueprint $table) {
            $table->dropColumn(['foto', 'foto_bawaan']);
        });
    }

    public function down(): void
    {
        Schema::table('profil_potensi', function (Blueprint $table) {
            $table->string('foto')->nullable();
            $table->string('foto_bawaan')->nullable();
        });

        Schema::table('profil_galeri', function (Blueprint $table) {
            $table->dropIndex(['potensi_id', 'urutan']);
            $table->dropConstrainedForeignId('potensi_id');
        });
    }
};
