<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_peserta_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->restrictOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->foreignId('participation_id')->constrained('participations')->restrictOnDelete();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->string('backfill_batch_id')->nullable();
            $table->integer('legacy_nip')->nullable();
            $table->string('legacy_participant_number')->nullable();
            $table->string('legacy_attendance_code')->nullable();
            $table->timestamp('migrated_at')->nullable();
            $table->timestamps();

            $table->unique('peserta_id', 'legacy_peserta_mappings_peserta_id_unique');
            $table->unique('participation_id', 'legacy_peserta_mappings_participation_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_peserta_mappings');
    }
};
