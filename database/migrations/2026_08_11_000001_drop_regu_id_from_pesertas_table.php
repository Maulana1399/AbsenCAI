<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pesertas', 'regu_id')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->dropReguIdSqlite();
        } else {
            $this->dropReguIdGeneric();
        }
    }

    private function dropReguIdSqlite(): void
    {
        // SQLite cannot DROP COLUMN when the column is referenced by a
        // table-level FOREIGN KEY definition embedded in the CREATE TABLE
        // statement. ALTER TABLE ... DROP COLUMN fails with:
        //   "unknown column 'regu_id' in foreign key definition"
        //
        // Explicit table rebuild is required. Same pattern as the existing
        // rebuild migration (make_jenis_kelamin_nullable_on_pesertas_table).
        // Indexes created during Schema::create use the temporary table name
        // as prefix (pesertas_sprint8b_*) and keep it after RENAME (SQLite
        // does NOT rename indexes during ALTER TABLE RENAME). This is
        // consistent with how the rebuild migration left its indexes and
        // no future migration needs to drop them by name.

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            // 1. Create new table without regu_id column, its FK, and its index.
            //    Preserve all other FKs and constraints.
            Schema::create('pesertas_sprint8b', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->integer('nip');
                $table->string('status_registrasi')->default('Belum Registrasi');
                $table->string('jenis_kelamin')->nullable();
                $table->string('jenis_peserta')->default('Wajib');
                $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks')->cascadeOnDelete();
                $table->foreignId('desa_id')->nullable()->constrained('desas')->cascadeOnDelete();
                $table->timestamps();
                $table->string('participant_number')->nullable();
                $table->string('attendance_code')->nullable();
                $table->unique('nip');
                $table->unique(['nama', 'desa_id', 'kelompok_id']);
                $table->unique('participant_number');
                $table->unique('attendance_code');
            });

            // 2. Copy data (explicit column list, exclude regu_id)
            DB::statement('
                INSERT INTO pesertas_sprint8b (
                    id, nama, nip, status_registrasi, jenis_kelamin,
                    jenis_peserta, kelompok_id, desa_id,
                    created_at, updated_at,
                    participant_number, attendance_code
                )
                SELECT
                    id, nama, nip, status_registrasi, jenis_kelamin,
                    jenis_peserta, kelompok_id, desa_id,
                    created_at, updated_at,
                    participant_number, attendance_code
                FROM pesertas
            ');

            // 3. Drop old table (all its indexes are dropped too)
            Schema::drop('pesertas');

            // 4. Rename new table
            DB::statement('ALTER TABLE pesertas_sprint8b RENAME TO pesertas');
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function dropReguIdGeneric(): void
    {
        // MySQL: the PRAGMA-based rebuild migration is SQLite-only, so MySQL
        // retains the original FK name (pesertas_regu_id_foreign) which
        // matches Laravel's naming convention. Drop FK explicitly, then drop
        // the column (MySQL auto-removes single-column indexes on DROP COLUMN).
        // Repeatable for other non-SQLite drivers via try/catch.
        try {
            Schema::table('pesertas', function (Blueprint $table) {
                $table->dropForeign(['regu_id']);
            });
        } catch (Throwable) {
            // FK may not exist or have a different name — proceed to drop column
        }

        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropColumn('regu_id');
        });
    }

    public function down(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->foreignId('regu_id')
                ->nullable()
                ->constrained('regus')
                ->onDelete('cascade');
        });
    }
};
