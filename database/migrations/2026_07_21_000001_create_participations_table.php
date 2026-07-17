<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->string('participant_number')->nullable();
            $table->string('attendance_code')->nullable()->unique();
            $table->string('jenis_peserta')->default('Wajib');
            $table->timestamps();

            $table->unique(['event_id', 'person_id'], 'participations_event_person_unique');
            $table->unique(['event_id', 'participant_number'], 'participations_event_participant_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participations');
    }
};
