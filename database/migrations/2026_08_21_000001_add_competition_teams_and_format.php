<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Competition format (5 formats) + lifecycle status on the class (lomba).
        Schema::table('competition_classes', function (Blueprint $table) {
            $table->string('format', 40)->nullable()->after('gender')->default('individual_heat');
            $table->string('status', 30)->nullable()->after('format')->default('registration_open');
            $table->index(['event_id', 'status']);
        });

        // Team = satu kelompok per CompetitionClass (lomba).
        Schema::create('competition_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('competition_class_id')->constrained('competition_classes')->restrictOnDelete();
            $table->string('name');
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompoks')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['competition_class_id', 'name'], 'uniq_class_team_name');
            $table->unique(['competition_class_id', 'kelompok_id'], 'uniq_class_team_kelompok');
            $table->index('event_id');
            $table->index('competition_class_id');
        });

        // Team members: players + substitutes, linked to each member's
        // CompetitionRegistration (Person → Participation → CompetitionRegistration).
        Schema::create('competition_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_team_id')->constrained('competition_teams')->cascadeOnDelete();
            $table->foreignId('competition_registration_id')->constrained('competition_registrations')->restrictOnDelete();
            $table->boolean('is_substitute')->default(false);
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['competition_team_id', 'competition_registration_id'], 'uniq_team_member');
            $table->index('competition_registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_team_members');
        Schema::dropIfExists('competition_teams');

        Schema::table('competition_classes', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'status']);
            $table->dropColumn(['format', 'status']);
        });
    }
};
