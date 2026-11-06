<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sengaja tanpa IP atau data pengunjung: cukup untuk tahu marketplace mana yang paling dipakai.
        Schema::create('toko_klik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->nullable()->constrained('toko_produk')->nullOnDelete();
            $table->string('marketplace', 20);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['marketplace', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_klik');
    }
};
