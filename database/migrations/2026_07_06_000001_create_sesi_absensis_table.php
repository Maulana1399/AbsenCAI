<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_absensis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sesi');
            $table->date('tanggal');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::table('absensis', function (Blueprint $table) {
            $table->foreignId('sesi_id')->nullable()->constrained('sesi_absensis')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sesi_id');
        });

        Schema::dropIfExists('sesi_absensis');
    }
};
