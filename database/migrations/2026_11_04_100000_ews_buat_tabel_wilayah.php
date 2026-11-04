<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ews_wilayah', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->json('poligon');
            $table->boolean('status_awas')->default(false);
            $table->string('awas_sumber')->nullable();
            $table->timestamp('awas_sejak')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ews_wilayah');
    }
};
