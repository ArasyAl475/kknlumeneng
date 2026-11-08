<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko_panen_jamur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_panen')->index();
            $table->decimal('jumlah_kg', 10, 2);
            $table->boolean('dengan_cangkang_kopi')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_panen_jamur');
    }
};
