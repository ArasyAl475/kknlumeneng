<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko_penjualan_jamur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_penjualan')->index();
            $table->decimal('jumlah_kg', 10, 2);
            $table->unsignedBigInteger('total_rupiah');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_penjualan_jamur');
    }
};
