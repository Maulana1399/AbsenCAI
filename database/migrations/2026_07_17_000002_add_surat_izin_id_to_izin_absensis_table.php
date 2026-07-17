<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('izin_absensis', function (Blueprint $table) {
            $table->foreignId('surat_izin_id')
                ->nullable()
                ->after('source')
                ->constrained('surat_izins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('izin_absensis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('surat_izin_id');
        });
    }
};
