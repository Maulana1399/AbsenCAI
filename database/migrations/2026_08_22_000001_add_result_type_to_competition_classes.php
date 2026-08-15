<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Explicit result type for the Result Engine (additive).
        // Null = fall back to CompetitionFormat::defaultResultType(format) at runtime.
        Schema::table('competition_classes', function (Blueprint $table) {
            $table->string('result_type', 20)->nullable()->after('status');
        });

        // Backfill existing classes from their format so behavior is deterministic.
        DB::statement("
            UPDATE competition_classes
            SET result_type = CASE format
                WHEN 'individual_heat' THEN 'time'
                WHEN 'individual_mass' THEN 'ranking'
                WHEN 'team_vs_team' THEN 'win_loss'
                WHEN 'team_mass' THEN 'ranking'
                WHEN 'individual_vs_individual' THEN 'score'
                ELSE 'ranking'
            END
            WHERE result_type IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('competition_classes', function (Blueprint $table) {
            $table->dropColumn('result_type');
        });
    }
};
