<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Boleh kosong karena catatan lama tidak mencatat siapa yang menimbang; di tabel tampil "–".
    public function up(): void
    {
        Schema::table('toko_panen_kopi', function (Blueprint $table) {
            $table->string('nama_petani', 150)->nullable()->after('tempat_panen');
        });
    }

    public function down(): void
    {
        Schema::table('toko_panen_kopi', function (Blueprint $table) {
            $table->dropColumn('nama_petani');
        });
    }
};
