<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_committee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->foreignId('participation_id')->nullable()->constrained('participations')->nullOnDelete();
            $table->foreignId('event_role_id')->constrained('event_roles')->restrictOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'person_id', 'event_role_id'], 'eca_event_person_role_unique');
            $table->index('event_id');
            $table->index('person_id');
            $table->index('event_role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_committee_assignments');
    }
};
