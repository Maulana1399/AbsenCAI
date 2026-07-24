<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participations', function (Blueprint $table) {
            $table->string('status_registrasi', 50)->nullable()->after('jenis_peserta');
        });
    }

    public function down(): void
    {
        Schema::table('participations', function (Blueprint $table) {
            $table->dropColumn('status_registrasi');
        });
    }
};
