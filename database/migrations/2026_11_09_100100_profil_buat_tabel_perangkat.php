<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_perangkat', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('jabatan', 100);
            // Menentukan letak di bagan struktur (kepala, sekretaris, kaur, kasi, kadus).
            $table->string('kelompok', 20);
            $table->string('foto')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['kelompok', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_perangkat');
    }
};
