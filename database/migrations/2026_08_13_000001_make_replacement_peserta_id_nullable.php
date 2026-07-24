<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cai_participant_replacements', function (Blueprint $table) {
            $table->foreignId('peserta_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('cai_participant_replacements', function (Blueprint $table) {
            $table->foreignId('peserta_id')
                ->nullable(false)
                ->change();
        });
    }
};
