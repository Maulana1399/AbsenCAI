<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_schedule_id')->constrained('competition_schedules')->cascadeOnDelete();
            $table->foreignId('competition_registration_id')->constrained('competition_registrations')->restrictOnDelete();
            $table->unsignedInteger('order_number')->nullable();
            $table->string('lane', 50)->nullable();
            $table->string('corner', 50)->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['competition_schedule_id', 'competition_registration_id'], 'uniq_schedule_registration');
            $table->index('competition_schedule_id');
            $table->index('competition_registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_schedule_entries');
    }
};
