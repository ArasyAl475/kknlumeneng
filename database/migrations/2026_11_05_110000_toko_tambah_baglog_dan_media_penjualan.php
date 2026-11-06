<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Kolom dibuat boleh kosong karena catatan lama belum punya data ini; isiannya tetap
    // wajib di formulir untuk catatan baru.
    public function up(): void
    {
        Schema::table('toko_panen_jamur', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_baglog')->nullable()->after('jumlah_kg');
            $table->unsignedBigInteger('biaya_baglog_rupiah')->nullable()->after('jumlah_baglog');
        });

        Schema::table('toko_penjualan_jamur', function (Blueprint $table) {
            $table->boolean('dengan_cangkang_kopi')->nullable()->after('jumlah_kg');
        });
    }

    public function down(): void
    {
        Schema::table('toko_penjualan_jamur', function (Blueprint $table) {
            $table->dropColumn('dengan_cangkang_kopi');
        });

        Schema::table('toko_panen_jamur', function (Blueprint $table) {
            $table->dropColumn(['jumlah_baglog', 'biaya_baglog_rupiah']);
        });
    }
};
