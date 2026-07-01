<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('pesertas_new', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('nip');
            $table->string('status_registrasi')->default('Belum Registrasi');
            $table->string('jenis_kelamin')->nullable();
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks')->cascadeOnDelete();
            $table->foreignId('desa_id')->nullable()->constrained('desas')->cascadeOnDelete();
            $table->foreignId('regu_id')->nullable()->constrained('regus')->cascadeOnDelete();
            $table->timestamps();
        });

        DB::statement(
            'INSERT INTO pesertas_new (id, nama, nip, status_registrasi, jenis_kelamin, kelompok_id, desa_id, regu_id, created_at, updated_at)
             SELECT id, nama, nip, COALESCE(status_registrasi, "Belum Registrasi"), jenis_kelamin, kelompok_id, desa_id, regu_id, created_at, updated_at
             FROM pesertas'
        );

        Schema::drop('pesertas');
        DB::statement('ALTER TABLE pesertas_new RENAME TO pesertas');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('pesertas_old', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('nip');
            $table->string('status_registrasi')->default('Belum Registrasi');
            $table->enum('jenis_kelamin', ['Laki - Laki', 'Perempuan']);
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks')->cascadeOnDelete();
            $table->foreignId('desa_id')->nullable()->constrained('desas')->cascadeOnDelete();
            $table->foreignId('regu_id')->nullable()->constrained('regus')->cascadeOnDelete();
            $table->timestamps();
        });

        DB::statement(
            'INSERT INTO pesertas_old (id, nama, nip, status_registrasi, jenis_kelamin, kelompok_id, desa_id, regu_id, created_at, updated_at)
             SELECT id, nama, nip, COALESCE(status_registrasi, "Belum Registrasi"), COALESCE(jenis_kelamin, "Laki - Laki"), kelompok_id, desa_id, regu_id, created_at, updated_at
             FROM pesertas'
        );

        Schema::drop('pesertas');
        DB::statement('ALTER TABLE pesertas_old RENAME TO pesertas');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
