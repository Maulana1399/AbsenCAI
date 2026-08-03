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
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks')->onDelete('cascade'); 
            $table->foreignId('desa_id')->nullable()->constrained('desas')->onDelete('cascade');
            $table->foreignId('regu_id')->nullable()->constrained('regus')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesertas');
    }
};
