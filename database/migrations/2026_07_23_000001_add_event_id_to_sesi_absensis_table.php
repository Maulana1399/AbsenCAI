<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_absensis', function (Blueprint $table) {
            $table->foreignId('event_id')
                ->nullable()
                ->after('id')
                ->constrained('events')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sesi_absensis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
