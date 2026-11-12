<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edukasi_materi', function (Blueprint $table) {
            // Nomor bagian materi di dalam kategorinya: Bagian 1, 2, 3, dst.
            $table->unsignedInteger('urutan')->default(0)->after('slug');
            $table->index(['kategori_id', 'urutan']);
        });

        // Materi yang sudah ada diberi nomor menurut urutan pembuatannya di tiap kategori.
        DB::table('edukasi_materi')
            ->orderBy('kategori_id')
            ->orderBy('id')
            ->get(['id', 'kategori_id'])
            ->groupBy('kategori_id')
            ->each(function ($daftar) {
                foreach ($daftar->values() as $indeks => $materi) {
                    DB::table('edukasi_materi')->where('id', $materi->id)->update(['urutan' => $indeks + 1]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('edukasi_materi', function (Blueprint $table) {
            $table->dropIndex(['kategori_id', 'urutan']);
        });

        Schema::table('edukasi_materi', function (Blueprint $table) {
            $table->dropColumn('urutan');
        });
    }
};
