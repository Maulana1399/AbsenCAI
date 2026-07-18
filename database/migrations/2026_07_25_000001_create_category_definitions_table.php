<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event_id', 'name']);
            $table->unique(['event_id', 'code']);
            $table->index('event_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_definitions');
    }
};
