<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris = satu tempat gambar tata letak di situs (foto hero, latar halaman masuk, dst.).
        Schema::create('desain_slot_gambar', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 60)->unique();
            $table->string('nama', 100);
            $table->string('halaman', 60)->index();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->text('penjelasan');
            $table->string('ukuran_saran', 80);
            // Rasio dipakai kotak pratinjau di admin agar bentuknya sama dengan tampilan di situs.
            $table->string('rasio', 10)->default('16/9');
            // Berkas bawaan di public/images/default; tidak pernah ditimpa unggahan admin.
            $table->string('gambar_bawaan');
            // Berkas unggahan admin di storage publik. Kosong = memakai gambar bawaan.
            $table->string('path')->nullable();
            $table->string('alt', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desain_slot_gambar');
    }
};
