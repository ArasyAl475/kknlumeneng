<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko_panen_kopi', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_panen')->index();
            $table->string('jenis_kopi', 100)->index();
            $table->string('tempat_panen', 150)->index();
            $table->decimal('jumlah_kg', 10, 2);
            $table->decimal('limbah_kg', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_panen_kopi');
    }
};
