<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->json('poligon');                       // [[lat,lng], [lat,lng], ...]
            $table->boolean('status_awas')->default(false); // ditahan sampai admin mencabut
            $table->string('awas_sumber')->nullable();
            $table->timestamp('awas_sejak')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
