<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_izins', function (Blueprint $table) {
            $table->string('jenis_izin', 20)->default('pulang')->after('alasan');
        });
    }

    public function down(): void
    {
        Schema::table('surat_izins', function (Blueprint $table) {
            $table->dropColumn('jenis_izin');
        });
    }
};
