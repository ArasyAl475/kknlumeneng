<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_lembaga', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('singkatan', 20)->nullable();
            $table->string('ketua', 100)->nullable();
            $table->string('keterangan', 300)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_lembaga');
    }
};
