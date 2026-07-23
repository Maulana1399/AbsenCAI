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

        Schema::table('participations', function (Blueprint $table) {
            $table->foreignId('regu_id')
                ->nullable()
                ->constrained('regus')
                ->nullOnDelete();
            $table->index('regu_id');
        });

        DB::statement('
            UPDATE participations
            SET regu_id = (
                SELECT pesertas.regu_id
                FROM legacy_participation_mappings
                INNER JOIN pesertas ON pesertas.id = legacy_participation_mappings.peserta_id
                WHERE legacy_participation_mappings.participation_id = participations.id
                LIMIT 1
            )
            WHERE regu_id IS NULL
        ');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        Schema::table('participations', function (Blueprint $table) {
            $table->dropIndex(['regu_id']);
            $table->dropForeign(['regu_id']);
            $table->dropColumn('regu_id');
        });
    }
};
