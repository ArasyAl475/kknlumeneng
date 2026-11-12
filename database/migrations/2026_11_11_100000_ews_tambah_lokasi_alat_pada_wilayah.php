<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ews_wilayah', function (Blueprint $table) {
            // Lokasi alat EWS di dalam wilayah. Boleh kosong: wilayah lama hanya punya batas gambar.
            $table->decimal('lintang', 9, 6)->nullable()->after('poligon');
            $table->decimal('bujur', 9, 6)->nullable()->after('lintang');
        });
    }

    public function down(): void
    {
        Schema::table('ews_wilayah', function (Blueprint $table) {
            $table->dropColumn(['lintang', 'bujur']);
        });
    }
};
