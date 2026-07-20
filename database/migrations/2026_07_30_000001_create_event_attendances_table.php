<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participation_id')->constrained('participations')->restrictOnDelete();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('desa_id')->nullable()->constrained('desas')->nullOnDelete();
            $table->dateTime('attended_at');
            $table->string('method', 20);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('participation_id', 'event_attendances_participation_unique');
            $table->index('event_id');
            $table->index('desa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendances');
    }
};
