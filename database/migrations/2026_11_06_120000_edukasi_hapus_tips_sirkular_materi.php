<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tips sirkularitas ternyata hanya diatur per kategori. Kolom per materi sempat dibuat
        // di lokal, jadi dibuang hanya bila ada agar aman di database yang belum memilikinya.
        if (Schema::hasColumn('edukasi_materi', 'tips_sirkular')) {
            Schema::table('edukasi_materi', function (Blueprint $table) {
                $table->dropColumn('tips_sirkular');
            });
        }
    }

    public function down(): void
    {
        // Sengaja kosong: kolomnya tidak dipakai lagi.
    }
};
