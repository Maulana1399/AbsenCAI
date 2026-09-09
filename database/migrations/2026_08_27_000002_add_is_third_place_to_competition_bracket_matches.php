<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penanda Bronze Match (Perebutan Juara 3) pada bracket match.
        // round=1, position=2, is_third_place=true = Bronze Match.
        Schema::table('competition_bracket_matches', function (Blueprint $table) {
            $table->boolean('is_third_place')->default(false)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('competition_bracket_matches', function (Blueprint $table) {
            $table->dropColumn('is_third_place');
        });
    }
};
