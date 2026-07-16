<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('izin_absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->foreignId('sesi_id')->constrained('sesi_absensis')->cascadeOnDelete();
            $table->string('status')->default('izin');
            $table->string('source')->default('manual');
            $table->timestamps();

            $table->unique(['peserta_id', 'sesi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izin_absensis');
    }
};