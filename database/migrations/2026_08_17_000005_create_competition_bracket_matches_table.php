<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_bracket_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_bracket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_schedule_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round');
            $table->unsignedInteger('position');
            $table->foreignId('source_match_a_id')->nullable()->constrained('competition_bracket_matches')->nullOnDelete();
            $table->foreignId('source_match_b_id')->nullable()->constrained('competition_bracket_matches')->nullOnDelete();
            $table->timestamps();

            $table->unique(['competition_bracket_id', 'round', 'position'], 'uniq_bracket_round_pos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_bracket_matches');
    }
};
