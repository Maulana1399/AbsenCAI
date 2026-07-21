<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cai_participant_replacements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->restrictOnDelete();

            // Slot peserta legacy CAI yang tetap dipertahankan.
            $table->foreignId('peserta_id')
                ->constrained('pesertas')
                ->restrictOnDelete();

            // Identitas lama sebelum diganti.
            $table->foreignId('old_person_id')
                ->constrained('people')
                ->restrictOnDelete();

            $table->foreignId('old_participation_id')
                ->constrained('participations')
                ->restrictOnDelete();

            // Identitas baru yang mengisi slot CAI.
            $table->foreignId('new_person_id')
                ->constrained('people')
                ->restrictOnDelete();

            $table->foreignId('new_participation_id')
                ->constrained('participations')
                ->restrictOnDelete();

            // Snapshot identitas operasional saat penggantian.
            $table->integer('legacy_nip')->nullable();
            $table->string('participant_number')->nullable();
            $table->string('attendance_code')->nullable();

            // Snapshot penempatan slot.
            $table->foreignId('desa_id')
                ->nullable()
                ->constrained('desas')
                ->nullOnDelete();

            $table->foreignId('kelompok_id')
                ->nullable()
                ->constrained('kelompoks')
                ->nullOnDelete();

            $table->foreignId('regu_id')
                ->nullable()
                ->constrained('regus')
                ->nullOnDelete();

            $table->text('reason')->nullable();

            // Nanti bisa diisi user admin yang melakukan replacement.
            $table->foreignId('replaced_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('replaced_at');

            $table->timestamps();

            $table->index(['event_id', 'peserta_id']);
            $table->index(['old_person_id', 'new_person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cai_participant_replacements');
    }
};