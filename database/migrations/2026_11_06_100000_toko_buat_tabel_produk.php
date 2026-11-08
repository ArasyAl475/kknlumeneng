<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko_produk', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 120);
            $table->string('slug', 140)->unique();
            $table->text('deskripsi')->nullable();
            // Rupiah utuh. Kosong berarti harga hanya dilihat di marketplace, atau ditentukan per varian.
            $table->unsignedInteger('harga')->nullable();
            $table->boolean('tersedia')->default(true);
            $table->boolean('tampil')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            // Daftar {label, nilai}; bentuknya bebas karena data tiap produk berbeda dan belum lengkap.
            $table->json('spesifikasi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tampil', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_produk');
    }
};
