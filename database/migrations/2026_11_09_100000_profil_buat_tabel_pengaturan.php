<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pasangan kunci-nilai, agar menambah isian profil baru tidak perlu migrasi baru.
        Schema::create('profil_pengaturan', function (Blueprint $table) {
            $table->string('kunci', 60)->primary();
            // mediumText karena sambutan dan sejarah berformat HTML bisa panjang.
            $table->mediumText('nilai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_pengaturan');
    }
};
