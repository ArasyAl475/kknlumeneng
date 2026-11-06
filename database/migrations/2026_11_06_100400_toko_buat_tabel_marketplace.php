<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko_marketplace', function (Blueprint $table) {
            // Nama, logo, dan domain sah ada di App\Services\Toko\DaftarMarketplace; di sini hanya isian admin.
            $table->string('kunci', 20)->primary();
            $table->string('url_toko', 500)->nullable();
            $table->string('url_dasbor', 500)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_marketplace');
    }
};
