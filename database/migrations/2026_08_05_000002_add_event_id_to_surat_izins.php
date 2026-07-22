<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_izins', function (Blueprint $table) {
            $table->foreignId('event_id')
                ->nullable()
                ->after('peserta_id')
                ->constrained('events')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surat_izins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
