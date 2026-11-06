<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko_varian_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('toko_produk')->cascadeOnDelete();
            $table->string('nama', 60);
            $table->unsignedInteger('harga')->nullable();
            $table->boolean('tersedia')->default(true);
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['produk_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_varian_produk');
    }
};
