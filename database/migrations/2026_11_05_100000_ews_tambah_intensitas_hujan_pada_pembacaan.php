<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Alat memakai sensor hujan MH-RD yang hanya mengukur seberapa basah papannya (0-100%), bukan mm/jam.
    // Kolom curah_hujan lama sengaja dibiarkan agar riwayat yang sudah tersimpan tidak hilang.
    public function up(): void
    {
        Schema::table('ews_pembacaan', function (Blueprint $table) {
            $table->decimal('intensitas_hujan', 5, 2)->nullable()->after('curah_hujan');
        });
    }

    public function down(): void
    {
        Schema::table('ews_pembacaan', function (Blueprint $table) {
            $table->dropColumn('intensitas_hujan');
        });
    }
};
