<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('activity_id')->constrained('activities')->restrictOnDelete();
            $table->foreignId('category_definition_id')->constrained('category_definitions')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'category_definition_id']);
            $table->index('event_id');
            $table->index('activity_id');
            $table->index('category_definition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_categories');
    }
};
