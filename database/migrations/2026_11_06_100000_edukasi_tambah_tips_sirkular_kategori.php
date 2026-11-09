<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edukasi_kategori', function (Blueprint $table) {
            // Satu tips sirkularitas per kategori; kosong berarti kategori itu belum punya tips.
            $table->text('tips_sirkular')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('edukasi_kategori', function (Blueprint $table) {
            $table->dropColumn('tips_sirkular');
        });
    }
};
