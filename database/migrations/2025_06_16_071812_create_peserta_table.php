<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pesertas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('nip');
            $table->enum('jenis_kelamin',['Laki - Laki', 'Perempuan']);
            $table->foreignId('kelompok_id')->constrained('kelompoks')->onDelete('cascade')->nullable(); 
            $table->foreignId('desa_id')->constrained('desas')->onDelete('cascade')->nullable();
            $table->foreignId('regu_id')->constrained('regus')->onDelete('cascade')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta');
    }
};
