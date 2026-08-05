<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Since the database is clean-state, we can safely drop columns entirely.
        // SQLite does NOT support DROP COLUMN for columns referenced by inline
        // table constraints (UNIQUE is inline). Use table rebuild instead.

        $driver = DB::connection()->getDriverName();

        // 1. Drop people.nip column
        if (Schema::hasColumn('people', 'nip')) {
            // Drop unique index explicitly for both SQLite and MySQL.
            // SQLite's DROP COLUMN fails if a UNIQUE index references the column.
            if ($driver === 'sqlite') {
                try {
                    DB::statement('DROP INDEX IF EXISTS people_nip_unique');
                } catch (Throwable) {
                }
            } else {
                try {
                    Schema::table('people', fn (Blueprint $t) => $t->dropUnique(['nip']));
                } catch (Throwable) {
                }
            }
            Schema::table('people', function (Blueprint $table) {
                $table->dropColumn('nip');
            });
        }

        // 2. Drop pesertas.nip (UNIQUE was added by separate migration, column is NOT NULL)
        //    On SQLite, drop the unique index first (may have renamed index), then column.
        //    On MySQL, drop unique then column.
        if (Schema::hasColumn('pesertas', 'nip')) {
            if ($driver !== 'sqlite') {
                try {
                    Schema::table('pesertas', fn (Blueprint $t) => $t->dropUnique(['nip']));
                } catch (Throwable) {
                }
                Schema::table('pesertas', function (Blueprint $table) {
                    $table->dropColumn('nip');
                });
            } else {
                // SQLite: DROP COLUMN fails if column is referenced by an index
                // (pesertas_sprint8b_nip_unique from Sprint 8B table rebuild).
                // Drop the index first, then drop the column.
                try {
                    DB::statement('DROP INDEX IF EXISTS pesertas_sprint8b_nip_unique');
                } catch (Throwable) {
                }

                DB::statement('PRAGMA foreign_keys = OFF');
                Schema::table('pesertas', function (Blueprint $table) {
                    $table->dropColumn('nip');
                });
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            if (! Schema::hasColumn('people', 'nip')) {
                $table->integer('nip')->nullable()->unique();
            }
        });

        Schema::table('pesertas', function (Blueprint $table) {
            if (! Schema::hasColumn('pesertas', 'nip')) {
                $table->integer('nip');
                $table->unique('nip');
            }
        });
    }
};
