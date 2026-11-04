<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ews_pembacaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wilayah_id')->constrained('ews_wilayah')->restrictOnDelete();
            $table->decimal('kelembapan_tanah', 5, 2)->nullable();
            $table->decimal('curah_hujan', 6, 2)->nullable();
            $table->decimal('kemiringan', 5, 2)->nullable();
            $table->decimal('kelembapan_udara', 5, 2)->nullable();
            $table->boolean('kabel_putus')->default(false);
            $table->string('status', 10);
            $table->string('warna', 10);
            $table->string('pemicu')->nullable();
            $table->string('validasi', 10)->nullable();
            $table->timestamp('divalidasi_pada')->nullable();
            $table->foreignId('divalidasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['wilayah_id', 'created_at']);
            $table->index(['wilayah_id', 'validasi']);
            $table->index('validasi');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ews_pembacaan');
    }
};
