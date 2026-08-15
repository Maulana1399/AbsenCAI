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

        // 1. Schedule entries can reference a Team (team competitor) OR a
        //    registration (person). Registration competitor stays as-is.
        Schema::table('competition_schedule_entries', function (Blueprint $table) {
            $table->foreignId('competition_registration_id')->nullable()->change();
            $table->foreignId('competition_team_id')
                ->nullable()
                ->after('competition_registration_id')
                ->constrained('competition_teams')
                ->restrictOnDelete();

            $table->unique(['competition_schedule_id', 'competition_team_id'], 'uniq_schedule_team');
            $table->index('competition_team_id');
        });

        // 2. A match winner can be a Team (foundation for Team vs Team).
        Schema::table('competition_schedules', function (Blueprint $table) {
            $table->foreignId('winner_team_id')
                ->nullable()
                ->after('winner_registration_id')
                ->constrained('competition_teams')
                ->nullOnDelete();
        });

        // 3. Team result (final per team) — mirror of competition_outcomes.
        Schema::create('competition_team_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_team_id')->constrained('competition_teams')->cascadeOnDelete()->unique();
            $table->unsignedInteger('position')->nullable();
            $table->string('status', 50)->nullable();
            $table->decimal('score', 10, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('competition_team_id');
        });

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_team_outcomes');

        Schema::table('competition_schedules', function (Blueprint $table) {
            $table->dropForeign(['winner_team_id']);
            $table->dropColumn('winner_team_id');
        });

        Schema::table('competition_schedule_entries', function (Blueprint $table) {
            $table->dropUnique('uniq_schedule_team');
            $table->dropForeign(['competition_team_id']);
            $table->dropIndex(['competition_team_id']);
            $table->dropColumn('competition_team_id');
            $table->foreignId('competition_registration_id')->nullable(false)->change();
        });
    }
};
