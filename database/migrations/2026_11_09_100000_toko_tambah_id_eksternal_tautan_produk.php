<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('toko_tautan_produk', function (Blueprint $table) {
            // Nomor produk di marketplace (item_id Shopee). Kunci untuk mencocokkan produk saat sinkron,
            // karena nama produk bisa diubah di kedua sisi. Kosong untuk tautan yang diisi manual.
            $table->string('id_eksternal', 40)->nullable()->after('url');

            // Nilai kosong boleh berulang; yang terisi tidak boleh kembar per marketplace.
            $table->unique(['marketplace', 'id_eksternal']);
        });
    }

    public function down(): void
    {
        Schema::table('toko_tautan_produk', function (Blueprint $table) {
            $table->dropUnique(['marketplace', 'id_eksternal']);
            $table->dropColumn('id_eksternal');
        });
    }
};
