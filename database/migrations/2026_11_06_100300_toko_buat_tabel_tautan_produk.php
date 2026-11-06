<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko_tautan_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('toko_produk')->cascadeOnDelete();
            $table->string('marketplace', 20);
            $table->string('url', 500);
            // Disiapkan untuk tahap 2: 'api' bila tautan diisi oleh sinkron otomatis.
            $table->string('sumber', 10)->default('manual');
            $table->timestamp('terakhir_sinkron')->nullable();
            $table->timestamps();

            $table->unique(['produk_id', 'marketplace']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_tautan_produk');
    }
};
