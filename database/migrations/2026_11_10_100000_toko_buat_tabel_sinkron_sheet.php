<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko_sinkron_sheet', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 30);
            $table->unsignedBigInteger('catatan_id');
            // Sidik isi baris saat terakhir sama di sistem dan Sheet. Dari sini terlihat siapa yang berubah.
            $table->string('hash', 40);
            $table->timestamps();

            $table->unique(['jenis', 'catatan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_sinkron_sheet');
    }
};
