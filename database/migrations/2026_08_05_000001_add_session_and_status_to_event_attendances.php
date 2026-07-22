<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_attendances', function (Blueprint $table) {
            $table->foreignId('sesi_absensi_id')
                ->nullable()
                ->after('participation_id')
                ->constrained('sesi_absensis')
                ->nullOnDelete();

            $table->string('status', 20)
                ->default('hadir')
                ->after('method');
        });

        DB::statement('DROP INDEX IF EXISTS event_attendances_participation_unique');

        DB::statement('
            CREATE UNIQUE INDEX event_attendances_participation_unique
            ON event_attendances(participation_id)
            WHERE sesi_absensi_id IS NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX event_attendances_session_unique
            ON event_attendances(participation_id, sesi_absensi_id)
            WHERE sesi_absensi_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS event_attendances_session_unique');
        DB::statement('DROP INDEX IF EXISTS event_attendances_participation_unique');

        Schema::table('event_attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sesi_absensi_id');
            $table->dropColumn('status');
        });

        Schema::table('event_attendances', function (Blueprint $table) {
            $table->unique('participation_id', 'event_attendances_participation_unique');
        });
    }
};
