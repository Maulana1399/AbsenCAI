<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_class_id')->constrained('competition_classes')->restrictOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->string('status', 20)->default('Scheduled');
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->index('competition_class_id');
            $table->index('venue_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_schedules');
    }
};
