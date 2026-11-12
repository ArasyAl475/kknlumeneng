<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko_shopee_token', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->unique();
            $table->string('nama_toko', 120)->nullable();
            // Dienkripsi cast model dengan APP_KEY; text karena hasil enkripsi jauh lebih panjang dari tokennya.
            $table->text('access_token');
            $table->text('refresh_token');
            $table->dateTime('akses_kedaluwarsa_pada');
            $table->dateTime('refresh_kedaluwarsa_pada');
            $table->foreignId('terhubung_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_shopee_token');
    }
};
