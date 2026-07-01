<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->string('status_registrasi')->default('Belum Registrasi')->after('nip');
        });

        DB::table('pesertas')
            ->whereNull('status_registrasi')
            ->update(['status_registrasi' => 'Belum Registrasi']);
    }

    public function down(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropColumn('status_registrasi');
        });
    }
};
