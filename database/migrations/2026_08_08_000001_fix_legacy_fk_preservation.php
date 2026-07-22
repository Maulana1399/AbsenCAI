<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_izins', function (Blueprint $table) {
            $table->integer('peserta_id')->nullable()->change();
        });

        Schema::table('izin_absensis', function (Blueprint $table) {
            $table->integer('peserta_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('surat_izins', function (Blueprint $table) {
            $table->integer('peserta_id')->nullable(false)->change();
        });

        Schema::table('izin_absensis', function (Blueprint $table) {
            $table->integer('peserta_id')->nullable(false)->change();
        });
    }
};
