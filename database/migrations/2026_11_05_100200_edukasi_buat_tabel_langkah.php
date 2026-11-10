<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edukasi_langkah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_id')->constrained('edukasi_materi')->cascadeOnDelete();
            $table->unsignedInteger('urutan');
            $table->string('judul', 150);
            $table->text('isi');
            $table->string('gambar')->nullable();
            $table->timestamps();

            $table->index(['materi_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edukasi_langkah');
    }
};
