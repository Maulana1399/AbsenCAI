<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rundown_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('rundown_id')->constrained('rundowns')->restrictOnDelete();
            $table->foreignId('activity_id')->constrained('activities')->restrictOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('sequence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['rundown_id', 'sequence']);
            $table->index('event_id');
            $table->index('activity_id');
            $table->index('venue_id');
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rundown_items');
    }
};
