<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->recreatePartialUniqueIndexesSqlite();
            return;
        }

        $this->recreateUniqueIndexesGeneric();
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->dropPartialUniqueIndexesSqlite();

            Schema::table('event_attendances', function (Blueprint $table) {
                $table->dropConstrainedForeignId('sesi_absensi_id');
                $table->dropColumn('status');
            });

            Schema::table('event_attendances', function (Blueprint $table) {
                $table->unique('participation_id', 'event_attendances_participation_unique');
            });

            return;
        }

        $this->dropUniqueIndexesGeneric();

        Schema::table('event_attendances', function (Blueprint $table) {
            $table->dropColumn(['sesi_absensi_id', 'status']);
        });
    }

    /**
     * SQLite supports partial (filtered) unique indexes. Enforce:
     *  - one attendance per participation when no session is attached
     *    (legacy pengajian attendance),
     *  - one attendance per (participation, session) for CAI attendance.
     */
    private function recreatePartialUniqueIndexesSqlite(): void
    {
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

    /**
     * MariaDB/MySQL do not support partial indexes. Replicate the same
     * uniqueness rules using generated columns:
     *  - legacy_participation_key holds participation_id only when
     *    sesi_absensi_id IS NULL (NULL otherwise), so the unique index
     *    allows one no-session attendance per participation.
     *  - a composite unique index on (participation_id, sesi_absensi_id)
     *    mirrors the session-scoped rule; rows without a session store NULL
     *    in sesi_absensi_id, which unique indexes treat as distinct, so the
     *    no-session rule is fully owned by legacy_participation_key.
     */
    private function recreateUniqueIndexesGeneric(): void
    {
        Schema::table('event_attendances', function (Blueprint $table) {
            $table->dropForeign(['participation_id']);
            $table->dropForeign(['sesi_absensi_id']);
            $table->dropUnique('event_attendances_participation_unique');
        });

        Schema::table('event_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_participation_key')
                ->nullable()
                ->after('status');

            $table->unique('legacy_participation_key', 'event_attendances_participation_unique');
            $table->unique(['participation_id', 'sesi_absensi_id'], 'event_attendances_session_unique');

            $table->foreign('participation_id')
                ->references('id')
                ->on('participations')
                ->restrictOnDelete();

            $table->foreign('sesi_absensi_id')
                ->references('id')
                ->on('sesi_absensis')
                ->nullOnDelete();
        });

        $this->createLegacyKeyTriggers();
    }

    private function dropPartialUniqueIndexesSqlite(): void
    {
        DB::statement('DROP INDEX IF EXISTS event_attendances_session_unique');
        DB::statement('DROP INDEX IF EXISTS event_attendances_participation_unique');
    }

    private function dropUniqueIndexesGeneric(): void
    {
        $this->dropLegacyKeyTriggers();

        Schema::table('event_attendances', function (Blueprint $table) {
            $table->dropForeign(['participation_id']);
            $table->dropForeign(['sesi_absensi_id']);
            $table->dropUnique('event_attendances_session_unique');
            $table->dropUnique('event_attendances_participation_unique');
            $table->dropColumn('legacy_participation_key');
        });

        Schema::table('event_attendances', function (Blueprint $table) {
            $table->unique('participation_id', 'event_attendances_participation_unique');

            $table->foreign('participation_id')
                ->references('id')
                ->on('participations')
                ->restrictOnDelete();
        });
    }

    /**
     * MariaDB cannot reference a column that is used inside a generated
     * column while also keeping a foreign key on it. The legacy key is
     * therefore kept as a plain column maintained by triggers instead of a
     * stored generated column.
     */
    private function createLegacyKeyTriggers(): void
    {
        DB::statement("
            CREATE TRIGGER event_attendances_legacy_key_bi
            BEFORE INSERT ON event_attendances
            FOR EACH ROW
            SET NEW.legacy_participation_key =
                IF(NEW.sesi_absensi_id IS NULL, NEW.participation_id, NULL)
        ");

        DB::statement("
            CREATE TRIGGER event_attendances_legacy_key_bu
            BEFORE UPDATE ON event_attendances
            FOR EACH ROW
            SET NEW.legacy_participation_key =
                IF(NEW.sesi_absensi_id IS NULL, NEW.participation_id, NULL)
        ");
    }

    private function dropLegacyKeyTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS event_attendances_legacy_key_bu');
        DB::statement('DROP TRIGGER IF EXISTS event_attendances_legacy_key_bi');
    }
};
