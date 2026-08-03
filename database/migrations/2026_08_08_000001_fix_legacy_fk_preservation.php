<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->dropForeignKeys();
        }

        Schema::table('surat_izins', function (Blueprint $table) {
            $table->unsignedBigInteger('peserta_id')->nullable()->change();
        });

        Schema::table('izin_absensis', function (Blueprint $table) {
            $table->unsignedBigInteger('peserta_id')->nullable()->change();
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->recreateForeignKeys();
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->dropForeignKeys();
        }

        Schema::table('surat_izins', function (Blueprint $table) {
            $table->unsignedBigInteger('peserta_id')->nullable(false)->change();
        });

        Schema::table('izin_absensis', function (Blueprint $table) {
            $table->unsignedBigInteger('peserta_id')->nullable(false)->change();
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->recreateForeignKeys();
        }
    }

    private function dropForeignKeys(): void
    {
        Schema::table('surat_izins', function (Blueprint $table) {
            $table->dropForeign(['peserta_id']);
        });

        Schema::table('izin_absensis', function (Blueprint $table) {
            $table->dropForeign(['peserta_id']);
        });
    }

    private function recreateForeignKeys(): void
    {
        Schema::table('surat_izins', function (Blueprint $table) {
            $table->foreign('peserta_id')->references('id')->on('pesertas')->cascadeOnDelete();
        });

        Schema::table('izin_absensis', function (Blueprint $table) {
            $table->foreign('peserta_id')->references('id')->on('pesertas')->cascadeOnDelete();
        });
    }
};
