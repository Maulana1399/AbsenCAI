<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-heat result (Individual Heat): one result per (schedule/heat, registration).
        // The final/aggregated ranking stays in competition_outcomes (unique per
        // registration) so existing podium/report/winner flows keep working.
        Schema::create('competition_heat_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_schedule_id')->constrained('competition_schedules')->cascadeOnDelete();
            $table->foreignId('competition_registration_id')->constrained('competition_registrations')->restrictOnDelete();
            $table->decimal('score', 10, 2)->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->string('status', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['competition_schedule_id', 'competition_registration_id'], 'uniq_heat_schedule_registration');
            $table->index('competition_registration_id');
            $table->index('competition_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_heat_results');
    }
};
