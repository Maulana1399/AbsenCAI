<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Multi-round Heat (R4H final): per-heat result kini boleh untuk registrasi
        // INDIVIDUAL (registration) ATAU TEAM (competition_team_id). Satu heat hanya
        // memakai salah satu — individual heat memakai registration, team heat memakai team.
        Schema::table('competition_heat_results', function (Blueprint $table) {
            $table->dropUnique('uniq_heat_schedule_registration');

            $table->foreignId('competition_registration_id')->nullable()->change();
            $table->foreignId('competition_team_id')->nullable()->after('competition_registration_id')
                ->constrained('competition_teams')->restrictOnDelete();

            $table->unique(['competition_schedule_id', 'competition_registration_id'], 'uniq_heat_schedule_registration');
            $table->unique(['competition_schedule_id', 'competition_team_id'], 'uniq_heat_schedule_team');
            $table->index('competition_team_id');
        });
    }

    public function down(): void
    {
        Schema::table('competition_heat_results', function (Blueprint $table) {
            $table->dropUnique('uniq_heat_schedule_team');
            $table->dropIndex(['competition_team_id']);
            $table->dropForeign(['competition_team_id']);
            $table->dropColumn('competition_team_id');

            $table->dropUnique('uniq_heat_schedule_registration');
            $table->foreignId('competition_registration_id')->nullable(false)->change();
            $table->unique(['competition_schedule_id', 'competition_registration_id'], 'uniq_heat_schedule_registration');
        });
    }
};
