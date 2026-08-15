<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        // User-based event membership: a User (with or without a linked Person)
        // can be directly assigned to an event role. person_id stays for the
        // existing Person-based membership; user_id is the new additive path.
        Schema::table('event_committee_assignments', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('event_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('user_id');
        });

        // Allow Guest assignments that have no Person at all.
        Schema::table('event_committee_assignments', function (Blueprint $table) {
            $table->foreignId('person_id')
                ->nullable()
                ->change();
        });

        // One active membership per (event, user, role) and (event, person, role).
        // NULL values are distinct in both MySQL and SQLite, so each unique index
        // only constrains the rows that actually carry that path.
        Schema::table('event_committee_assignments', function (Blueprint $table) {
            $table->unique(['event_id', 'user_id', 'event_role_id'], 'eca_event_user_role_unique');
        });

        // Backfill user_id from the Person → User link so that User-based
        // membership resolution also sees the existing Person-based memberships.
        DB::statement('
            UPDATE event_committee_assignments
            SET user_id = (
                SELECT users.id
                FROM users
                WHERE users.person_id = event_committee_assignments.person_id
                LIMIT 1
            )
            WHERE user_id IS NULL
              AND person_id IS NOT NULL
        ');

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        Schema::table('event_committee_assignments', function (Blueprint $table) {
            $table->dropUnique('eca_event_user_role_unique');
        });

        Schema::table('event_committee_assignments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
